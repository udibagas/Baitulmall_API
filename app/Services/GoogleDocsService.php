<?php

namespace App\Services;

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;
use Google\Service\Docs\Request;

class GoogleDocsService
{
    protected $client;
    protected $service;
    protected $driveService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Baitulmall Dashboard');
        $this->client->setScopes([Docs::DOCUMENTS, Drive::DRIVE]);
        
        $authConfig = storage_path('credentials.json');
        
        if (file_exists($authConfig)) {
            $this->client->setAuthConfig($authConfig);
            $this->client->setAccessType('offline');
        }
    }

    public function createDocument($title, $content)
    {
        if (!$this->client->getAccessToken() && !$this->client->isUsingApplicationDefaultCredentials()) {
             // If no credentials, we can't proceed. 
             // In a real app we might throw a specific error, but for now we'll check validity upstream or here.
             if (!file_exists(storage_path('credentials.json'))) {
                 throw new \Exception("Google Credentials not found in storage/credentials.json");
             }
        }

        $this->service = new Docs($this->client);
        $this->driveService = new Drive($this->client);

        // 1. Create a blank document
        $doc = new \Google\Service\Docs\Document([
            'title' => $title
        ]);
        
        $createdDoc = $this->service->documents->create($doc);
        $documentId = $createdDoc->documentId;

        // 2. Insert content
        // Google Docs API works with structural requests, not raw HTML. 
        // For simplicity in this v1, we will strip tags and insert plain text.
        // A more advanced version would parse HTML to Docs Requests.
        
        $plainText = strip_tags($content); // Basic strip for now
        // Decode HTML entities
        $plainText = html_entity_decode($plainText);

        $requests = [
            new Request([
                'insertText' => [
                    'text' => $plainText,
                    'location' => [
                        'index' => 1,
                    ],
                ],
            ]),
        ];

        $batchUpdateRequest = new \Google\Service\Docs\BatchUpdateDocumentRequest([
            'requests' => $requests
        ]);

        $this->service->documents->batchUpdate($documentId, $batchUpdateRequest);

        // 3. Make it viewable by anyone with the link (Optional, or just return the ID)
        // For privacy, we might NOT want this. But for ease of demo, let's keep it private 
        // and assume the user is signed into the browser with an account that has access 
        // IF we shared it. But Service Account owns it. 
        // To make it accessible to the user, we should Share it with their email, 
        // OR make it link-viewable. Let's make it link-viewable for now for simplicity.
        
        $permission = new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        
        $this->driveService->permissions->create($documentId, $permission);

        return "https://docs.google.com/document/d/" . $documentId . "/edit";
    }
}
