<?php

return [
    'array' => ':Attribute moet een lijst zijn.',
    'file' => ':Attribute moet een bestand zijn.',
    'image' => ':Attribute moet een afbeelding zijn.',
    'max' => [
        'array' => ':Attribute mag niet meer dan :max items bevatten.',
        'file' => ':Attribute mag niet groter zijn dan :max kilobytes.',
        'numeric' => ':Attribute mag niet groter zijn dan :max.',
        'string' => ':Attribute mag niet langer zijn dan :max tekens.',
    ],
    'required' => ':Attribute is verplicht.',
    'uploaded' => ':Attribute kon niet worden geupload.',

    'custom' => [
        'files.*' => [
            'file' => 'Elke upload moet een bestand zijn.',
            'max' => 'Elk bestand mag maximaal :max kilobytes zijn.',
            'uploaded' => 'Een bestand kon niet worden geupload. Controleer de bestandsgrootte en probeer opnieuw.',
        ],
        'attachment_files' => [
            'array' => 'Bijlagen moeten als bestandenlijst worden geupload.',
        ],
        'attachment_files.*' => [
            'file' => 'Elke bijlage moet een bestand zijn.',
            'max' => 'Elke bijlage mag maximaal :max kilobytes zijn.',
            'uploaded' => 'Een bijlage kon niet worden geupload. Controleer de bestandsgrootte en probeer opnieuw.',
        ],
        'incomingUploads' => [
            'max' => 'Je kunt maximaal :max bijlagen tegelijk klaarzetten.',
        ],
        'incomingUploads.*' => [
            'file' => 'Elke bijlage moet een bestand zijn.',
            'max' => 'Elke bijlage mag maximaal :max kilobytes zijn.',
            'uploaded' => 'Een bijlage kon niet worden geupload. Controleer de bestandsgrootte en probeer opnieuw.',
        ],
        'queuedUploads' => [
            'max' => 'Je kunt maximaal :max bijlagen tegelijk klaarzetten.',
        ],
        'queuedUploads.*' => [
            'file' => 'Elke bijlage moet een bestand zijn.',
            'max' => 'Elke bijlage mag maximaal :max kilobytes zijn.',
            'uploaded' => 'Een bijlage kon niet worden geupload. Controleer de bestandsgrootte en probeer opnieuw.',
        ],
        'uploads' => [
            'max' => 'Je kunt maximaal :max afbeeldingen tegelijk uploaden.',
        ],
        'uploads.*' => [
            'image' => 'Elke upload moet een afbeelding zijn.',
            'max' => 'Elke afbeelding mag maximaal :max kilobytes zijn.',
            'uploaded' => 'Een afbeelding kon niet worden geupload. Controleer de bestandsgrootte en probeer opnieuw.',
        ],
    ],

    'attributes' => [
        'files' => 'bestanden',
        'files.*' => 'bestand',
        'attachment_files' => 'bijlagen',
        'attachment_files.*' => 'bijlage',
        'incomingUploads' => 'bijlagen',
        'incomingUploads.*' => 'bijlage',
        'queuedUploads' => 'bijlagen',
        'queuedUploads.*' => 'bijlage',
        'queuedNames.*' => 'bijlage naam',
        'uploads' => 'afbeeldingen',
        'uploads.*' => 'afbeelding',
    ],
];
