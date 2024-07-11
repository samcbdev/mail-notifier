# Mail Notifier Package
## _The Well Organised mail Notification Manager, Ever_

A custom Laravel package for dynamic mail template creation and storage in a database, with the capability to send emails using Laravel Jobs.

## Features

- **Dynamic Mail Templates:** Create, store, and manage dynamic mail templates in the database.
- **Filtering:** Easily retrieve templates with custom filter conditions.
- **Dynamic Content Replacement:** Replace dynamic fields within templates.
- **Email Sending:** Send emails with support for CC, BCC, and attachments.
- **Error Handling:** Handles exceptions for cases where mail templates are not found.

## Tech Stack

- **Laravel:** A PHP framework for building modern web applications.
- **Composer:** Dependency manager for PHP.
- **MySQL:** Relational database management system for storing mail templates.
- 
## Installation

1. Composer Install
    Run the following command to install the package via Composer:
    ```sh
    composer require samcbdev/mail-notifier
    ```
2. Vendor Publish
    Publish the package's configuration and other necessary files:
    ```sh
    php artisan vendor:publish --tags="mail-notifier"
    ```
3. Migration Run
    Run the database migrations to create the necessary tables:
    ```sh
    php artisan migrate
    ```
## Usage Guide

1. Retrieve All
    Retrieve all mail templates with optional filtering:
    ```sh
    Mailer::filterCondition()->get();
    
    // With filters
    $filter = [
        'custom_fields->comp_id' => 1,
    ];
    Mailer::filterCondition($filter)->get();
    ```
    
 2. Retrieve One
    Retrieve a single mail template with filtering:
    ```sh
    $filter = [
        'custom_fields->comp_id' => 1,
    ];
    Mailer::filterCondition($filter, true)->get(); // true returns first matching record
    ```
    
3. Store
    Store a new mail template:
    ```sh
    $data = [
        'template_unique_id' => 'FP',
        'custom_fields' => [ // here custom field is json column
            'comp_id' => 1
        ],
        'title' => 'Title',
        'subject' => 'Subject',
        'content' => 'Content'
    ];

    $setUnique = ['template_unique_id', 'custom_fields->comp_id'];

    Mailer::storeData($setUnique, $data);
    ```
    
4. Edit
    Edit an existing mail template:
    ```sh
    $data = [
        'template_unique_id' => 'FP',
        'custom_fields' => [
            'comp_id' => 1
        ],
        'title' => 'Title',
        'subject' => 'Subject',
        'content' => 'Content'
    ];

    $setUnique = ['template_unique_id', 'custom_fields->comp_id'];
    
    Mailer::editData(1, $setUnique, $data);
    ```
    
5. Delete
    Delete a mail template:
    ```sh
    Mailer::deleteData(1);
    ```
    
6. Check Dynamic Strings
    Check for dynamic strings within a mail template:
    ```sh
    $filter = [
        'template_unique_id' => 'FP',
        'custom_fields->comp_id' => 1,
    ];
    Mailer::filterCondition($filter, true)->checkDynamicFields();
    ```
    
7. Replace Dynamic Strings and Send Mail
    Replace dynamic fields within a mail template and send an email:
    **setFromAddress** is opitonal functional. If needs to change the from email address and mail, add the function before **senMail** function.
    ```sh
    $filter = [
        'template_unique_id' => 'FP',
        'custom_fields->comp_id' => 1,
    ];
    
    $contentArray = [
        '[[content_key]]' => 'replacing string against the key',
    ];
    
    $subjectArray = [
        '[[subject_key]]' => 'replacing string against the key'
    ];

    $fromAddr = [
        'name' => 'testing',
        'email' => 'from@web.php'
    ];
    
    $to = ['recipient@example.com'];

    $ccOrBcc = [
        'cc' => ['cc1@example.com', 'cc2@example.com'],
        'bcc' => ['bcc1@example.com']
    ];
    
    $attachments = [
        storage_path('/app/public/img.jpg'),
        storage_path('/app/public/pdf.pdf')
    ];
    
    try {
        $ret = Mailer::filterCondition($filter, true)
            ->replaceDynamicFields($contentArray, $subjectArray)
            ->setFromAddress($fromAddr)
            ->sendMail($to, $ccOrBcc, $attachments);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Mail Not Send',
            'message' => $e->getMessage()
        ], 404);
    }
    ```

## Development

Open your favorite Terminal and run this command.

```sh
php artisan queue:work
```

## License

MIT
