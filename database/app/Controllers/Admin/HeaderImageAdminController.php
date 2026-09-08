<?php
namespace App\Controllers\Admin;

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Models\HeaderImage;
use App\Models\StoreSettings;

class HeaderImageAdminController
{
    public function index(Request $request): void
    {
        Response::success([
            'store_types' => StoreSettings::STORE_TYPES,
            'images' => HeaderImage::allGrouped(),
        ]);
    }

    public function upload(Request $request): void
    {
        $storeType = (string) $request->input('store_type', '');
        if (!in_array($storeType, StoreSettings::STORE_TYPES, true)) { Response::error('Invalid category', 422); return; }

        $file = $request->file('image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) { Response::error('No valid image uploaded', 422); return; }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) { Response::error('Only JPG, PNG, or WEBP images are allowed', 422); return; }

        $uploadDir = rtrim((string) Env::get('UPLOAD_DIR', __DIR__ . '/../../../public/uploads'), '/');
        $dir = "$uploadDir/header-images/$storeType";
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $filename = uniqid('hdr_') . '.' . $allowed[$mime];
        move_uploaded_file($file['tmp_name'], "$dir/$filename");
        $relativePath = "/uploads/header-images/$storeType/$filename";

        $label = trim((string) $request->input('label', ''));
        $id = HeaderImage::create($storeType, $relativePath, $label !== '' ? $label : null);

        Response::success(HeaderImage::find($id), 'Header image uploaded');
    }

    public function delete(Request $request): void
    {
        $id = (int) $request->param('id');
        $image = HeaderImage::find($id);
        if (!$image) { Response::error('Image not found', 404); return; }

        $uploadRoot = rtrim((string) Env::get('UPLOAD_DIR', __DIR__ . '/../../../public/uploads'), '/');
        $absolute = str_replace('/uploads', $uploadRoot, $image['image_path']);
        if (is_file($absolute)) @unlink($absolute);

        HeaderImage::delete($id);
        Response::success(null, 'Header image removed');
    }
}
