<?php

namespace Samcbdev\MailNotifier;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Samcbdev\MailNotifier\Jobs\SendEmailJob;
use Samcbdev\MailNotifier\Models\MailNotifier;

class Mailer
{
    private static $cachedData = null;
    private $contentData = [];
    private $data;
    private $fromAddress = [];

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Retrieve's data from table based on condition
     *
     * @param array $data Data to be stored (template_unique_id, custom_fields, title, subject, content).
     * @return Collection of model
     *
     * @author Samcbdev
     */
    public static function filterCondition($filter = [], $getOne = false, $perPage = 10)
    {
        if ($getOne) {
            self::$cachedData = MailNotifier::where($filter)->first();
        } else {
            self::$cachedData = MailNotifier::where($filter)->paginate($perPage);
        }

        return new self(self::$cachedData);
    }

    /**
     * Returns the data that retrived from filterCondition
     *
     * @return Collection of model
     *
     * @author Samcbdev
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Store a newly created record in the database.
     *
     * Validates input data and stores it in the MailNotifier table.
     *
     * @param array $data Data to be stored (template_unique_id, custom_fields, title, subject, content).
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     *
     * @author Samcbdev
     */
    public static function storeData($getUnique = [], $data = []): JsonResponse
    {
        // Define validation rules
        $validator = Validator::make($data, [
            'template_unique_id' => 'required|string|max:255',
            'custom_fields' => 'sometimes|array',
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        //unique validation
        $uniqueFilter = [];

        foreach ($getUnique as $value) {
            $exploded = explode('->', $value);

            if(count($exploded) <= 1)
            {
                $uniqueFilter[$value] = $data[$value] ?? null;
            } else {
                $uniqueFilter[$value] = $data[$exploded[0]][$exploded[1]] ?? null;
            }
        }

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $isExists = MailNotifier::where($uniqueFilter)->first();

        if (!$isExists) {
            $mailer = new MailNotifier();
            $mailer->template_unique_id = $data['template_unique_id'];
            $mailer->custom_fields = $data['custom_fields'];
            $mailer->title = $data['title'];
            $mailer->subject = $data['subject'];
            $mailer->content = $data['content'];
            $mailer->status = 1;
            $mailer->save();

            // Retrieve the newly created record
            $newMailer = MailNotifier::find($mailer->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Data stored successfully',
                'data' => $newMailer
            ], 201);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Template unique columns already exists'
        ], 409);
    }

    /**
     * Edit a created record in the database.
     *
     * Validates input data and updates it in the MailNotifier table.
     *
     * @param $id Primary key of the record
     * @param array $data Data to be updated (template_unique_id, custom_fields, title, subject, content).
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     *
     * @author Samcbdev
     */
    public static function editData($id, $getUnique, $data)
    {
        //unique validation
        $uniqueFilter = [];

        foreach ($getUnique as $value) {
            $exploded = explode('->', $value);

            if(count($exploded) <= 1)
            {
                $uniqueFilter[$value] = $data[$value] ?? null;
            } else {
                $uniqueFilter[$value] = $data[$exploded[0]][$exploded[1]] ?? null;
            }
        }

        $isExists = MailNotifier::where($uniqueFilter)->whereNot('id', $id)->first();

        if($isExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template unique columns already exists'
            ], 409);
        }

        $mailer = MailNotifier::find($id);

        if (!$mailer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template not exists'
            ], 409);
        }

        $mailer->template_unique_id = $data['template_unique_id'];
        $mailer->custom_fields = $data['custom_fields'];
        $mailer->title = $data['title'];
        $mailer->subject = $data['subject'];
        $mailer->content = $data['content'];
        $mailer->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data updated successfully',
            'data' => $mailer
        ], 201);
    }

    /**
     * Delete record in the database.
     *
     * Validates input id in the MailNotifier table.
     *
     * @param array $id Primary key of the record
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     *
     * @author Samcbdev
     */
    public static function deleteData($id)
    {
        $mailer = MailNotifier::find($id);

        if (!$mailer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found'
            ], 409);
        }

        // Soft delete the record
        $mailer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Record deleted successfully'
        ], 200);
    }

    /**
     * Returns the dynamic strings if it's available in subject and content
     *
     * @param array $data Is the instance of retrived data from constructor
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @author Samcbdev
     */
    public function checkDynamicFields($data = null)
    {
        if ($data) {
            $record = $data;
        } else {
            $record = $this->data;
        }

        // Check if the record is a single instance or a collection
        if ($record instanceof Collection || (is_array($record) && count($record) != 1)) {
            throw new ModelNotFoundException('The provided data is not a single collection.');
        }

        // check dynamic fields
        $contentString = $this->pregMatchData($record->content);
        $subjectString = $this->pregMatchData($record->subject);

        $replacedContent = [
            'content' => $contentString ?? $this->data->content,
            'subject' => $subjectString ?? $this->data->subject,
        ];

        $this->contentData = $replacedContent;
        return $this->contentData;
    }

    /**
     * Returns the preg matched data
     *
     * @param string $stringData Is a string that used to segegrate the dynamic strings.
     * @return array
     *
     * @author Samcbdev
     */
    public function pregMatchData($stringData)
    {
        $dynamic_fields_content = [];
        if (preg_match_all("/\[\[(.*?)\]\]/", $stringData, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $dynamic_fields_content[] = "[[" . "{$match[0]}" . "]]";
            }
        }

        $chk_dynamic_content = count($dynamic_fields_content);
        if ($chk_dynamic_content > 0) {
            $dynamic_fields_content = array_unique($dynamic_fields_content);
            $dynamicData = $dynamic_fields_content;
        }

        return $dynamicData ?? [];
    }

    /**
     * Returns the preg matched replaced string
     *
     * @param array $contentArray Is a array that holds a values to be change in content string
     *
     * @param array $subjectArray Is a array that holds a values to be change in subject string
     *
     * @return array
     *
     * @author Samcbdev
     */
    public function replaceDynamicFields($contentArray = [], $subjectArray = [])
    {
        if (!$this->data) {
            throw new ModelNotFoundException('No data available for replacement.');
        }

        $content = $this->data->content;
        $subject = $this->data->subject;

        if ($contentArray) {
            $contentString = str_replace(array_keys($contentArray), array_values($contentArray), $content);
        }

        if ($subjectArray) {
            $subjectString = str_replace(array_keys($subjectArray), array_values($subjectArray), $subject);
        }

        $replacedContent = [
            'content' => $contentString ?? $this->data->content,
            'subject' => $subjectString ?? $this->data->subject,
        ];
        $this->contentData = $replacedContent;
        return $this;
    }

    /**
     * Set from mail address and name
     *
     * @param array $fromAddress name and mail id
     *
     * @return void
     *
     * @author Samcbdev
     */
    public function setFromAddress($fromAddress = [])
    {
        $this->fromAddress = $fromAddress;
        return $this;
    }

    /**
     * Send a mail based on generated content using Job
     *
     * @param array $to to addresses
     *
     * @param array $ccOrBcc Holds the cc and bcc arrays
     *
     * @param array $attachments attachments
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @return void
     *
     * @author Samcbdev
     */
    public function sendMail($to = [], $ccOrBcc = [], $attachments = [])
    {
        if (empty($to)) {
            throw new ModelNotFoundException('Minimum one two address is mandatory.');
        }
        $dispatchData = $this->prepareDispatchData($to, $ccOrBcc, $attachments);
        SendEmailJob::dispatch($dispatchData, $this->fromAddress);
    }

    /**
     * Prepare the data for SendEmailJob
     *
     * @param array $to to addresses
     *
     * @param array $ccOrBcc Holds the cc and bcc arrays
     *
     * @param array $attachments attachments
     *
     * @return array
     *
     * @author Samcbdev
     */
    public function prepareDispatchData($to, $ccOrBcc, $attachments)
    {
        $dispatchData = [
            'mail_to' => $to,
            'cc' => $ccOrBcc['cc'] ?? [],
            'bcc' => $ccOrBcc['bcc'] ?? [],
            'subject' => $this->contentData['subject'],
            'content' => $this->contentData['content'],
            'attachments' => $attachments
        ];
        return $dispatchData;
    }
}
