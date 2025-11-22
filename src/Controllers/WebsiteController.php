<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;
use GoldenPalms\CRM\Config\Database;

class WebsiteController
{
    public function getContent(Request $request, Response $response): Response
    {
        try {
            // Initialize database if not already done
            Database::initialize();
            
            $queryParams = $request->getQueryParams();
            $page = $queryParams['page'] ?? null;
            $section = $queryParams['section'] ?? null;
            $includeUnpublished = isset($queryParams['all']) && $queryParams['all'] === 'true'; // Admin can request all versions

            $query = DB::table('website_content')
                ->orderBy('version', 'desc');
            
            // For admin editor, show all content. For public API, only published
            if (!$includeUnpublished) {
                $query->where('is_published', 1);
            }

            if ($page) {
                $query->where('page', $page);
            }

            if ($section) {
                $query->where('section', $section);
            }

            $content = $query->get();

            // Group by page.section and get latest version
            $grouped = [];
            foreach ($content as $item) {
                $key = $item->page . '.' . $item->section;
                if (!isset($grouped[$key]) || $item->version > $grouped[$key]->version) {
                    $grouped[$key] = $item;
                }
            }

            $response->getBody()->write(json_encode(array_values($grouped)));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function createContent(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Validate required fields
        if (empty($data['page']) || empty($data['section']) || empty($data['content_key'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Missing required fields: page, section, content_key'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Check if content already exists for this page/section/key
        $existing = DB::table('website_content')
            ->where('page', $data['page'])
            ->where('section', $data['section'])
            ->where('content_key', $data['content_key'])
            ->orderBy('version', 'desc')
            ->first();

        $version = $existing ? $existing->version + 1 : 1;

        $newContentId = DB::table('website_content')->insertGetId([
            'page' => $data['page'],
            'section' => $data['section'],
            'content_key' => $data['content_key'],
            'content_type' => $data['content_type'] ?? 'text',
            'content' => $data['content'] ?? '',
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'version' => $version,
            'is_published' => $data['is_published'] ?? false,
            'published_at' => ($data['is_published'] ?? false) ? Helper::now() : null,
            'created_by' => $userId,
            'created_at' => Helper::now()
        ]);

        // If publishing, unpublish old versions
        if ($data['is_published'] ?? false && $existing) {
            DB::table('website_content')
                ->where('page', $data['page'])
                ->where('section', $data['section'])
                ->where('content_key', $data['content_key'])
                ->where('id', '!=', $newContentId)
                ->update(['is_published' => 0]);
        }

        $newContent = DB::table('website_content')->where('id', $newContentId)->first();

        $response->getBody()->write(json_encode($newContent));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function updateContent(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $content = DB::table('website_content')->where('id', $id)->first();
        if (!$content) {
            $response->getBody()->write(json_encode([
                'error' => 'Content not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Create new version
        $newVersion = $content->version + 1;

        $newContentId = DB::table('website_content')->insertGetId([
            'page' => $content->page,
            'section' => $content->section,
            'content_key' => $content->content_key,
            'content_type' => $data['content_type'] ?? $content->content_type,
            'content' => $data['content'] ?? $content->content,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : $content->metadata,
            'version' => $newVersion,
            'is_published' => $data['is_published'] ?? $content->is_published,
            'published_at' => ($data['is_published'] ?? false) ? Helper::now() : null,
            'created_by' => $userId,
            'created_at' => Helper::now()
        ]);

        // If publishing, unpublish old version
        if ($data['is_published'] ?? false) {
            DB::table('website_content')
                ->where('page', $content->page)
                ->where('section', $content->section)
                ->where('content_key', $content->content_key)
                ->where('id', '!=', $newContentId)
                ->update(['is_published' => 0]);
        }

        $newContent = DB::table('website_content')->where('id', $newContentId)->first();

        $response->getBody()->write(json_encode($newContent));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function uploadMedia(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        
        if (empty($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode([
                'error' => 'No file uploaded'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $file = $uploadedFiles['file'];
        
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode([
                'error' => 'File upload error'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate file type and size
        $allowedTypes = explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,gif');
        $maxSize = (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880); // 5MB default

        $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            $response->getBody()->write(json_encode([
                'error' => 'File type not allowed'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if ($file->getSize() > $maxSize) {
            $response->getBody()->write(json_encode([
                'error' => 'File size exceeds maximum allowed size'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $uploadDir = __DIR__ . '/../../public/uploads/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->moveTo($uploadDir . $filename);

        $response->getBody()->write(json_encode([
            'filename' => $filename,
            'url' => '/uploads/' . $filename,
            'size' => $file->getSize(),
            'type' => $file->getClientMediaType()
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}

