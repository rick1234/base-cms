<?php

return [
    'custom' => [
        'files.*' => [
            'file' => 'Every upload must be a file.',
            'max' => 'Each file may be at most :max kilobytes.',
            'uploaded' => 'A file could not be uploaded. Check the file size and try again.',
        ],
        'attachment_files' => [
            'array' => 'The attachments must be uploaded as a file list.',
        ],
        'attachment_files.*' => [
            'file' => 'Every attachment must be a file.',
            'max' => 'Each attachment may be at most :max kilobytes.',
            'uploaded' => 'An attachment could not be uploaded. Check the file size and try again.',
        ],
        'incomingUploads' => [
            'max' => 'You can queue at most :max attachments at once.',
        ],
        'incomingUploads.*' => [
            'file' => 'Every attachment must be a file.',
            'max' => 'Each attachment may be at most :max kilobytes.',
            'uploaded' => 'An attachment could not be uploaded. Check the file size and try again.',
        ],
        'queuedUploads' => [
            'max' => 'You can queue at most :max attachments at once.',
        ],
        'queuedUploads.*' => [
            'file' => 'Every attachment must be a file.',
            'max' => 'Each attachment may be at most :max kilobytes.',
            'uploaded' => 'An attachment could not be uploaded. Check the file size and try again.',
        ],
        'uploads' => [
            'max' => 'You can upload at most :max images at once.',
        ],
        'uploads.*' => [
            'image' => 'Every upload must be an image.',
            'max' => 'Each image may be at most :max kilobytes.',
            'uploaded' => 'An image could not be uploaded. Check the file size and try again.',
        ],
    ],

    'attributes' => [
        'files' => 'files',
        'files.*' => 'file',
        'attachment_files' => 'attachments',
        'attachment_files.*' => 'attachment',
        'incomingUploads' => 'attachments',
        'incomingUploads.*' => 'attachment',
        'queuedUploads' => 'attachments',
        'queuedUploads.*' => 'attachment',
        'queuedNames.*' => 'attachment name',
        'uploads' => 'images',
        'uploads.*' => 'image',
    ],
];
