<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageUpload
{
    public const RULE = 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120';

    public static function storeUploadedFile(UploadedFile $file, string $directory): string
    {
        Storage::disk('public')->makeDirectory($directory);

        $path = $file->store($directory, 'public');

        if (! $path) {
            throw ValidationException::withMessages([
                'image' => 'Impossible d\'enregistrer l\'image. Vérifiez les droits du dossier storage.',
            ]);
        }

        return $path;
    }

    public static function assertValidUpload(Request $request, string $field): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);

        if ($file->isValid()) {
            return;
        }

        $message = match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'L\'image est trop volumineuse (maximum 5 Mo).',
            UPLOAD_ERR_PARTIAL => 'Le téléchargement de l\'image a été interrompu. Réessayez.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Le serveur ne peut pas enregistrer l\'image. Contactez l\'administrateur.',
            default => 'Le fichier n\'a pas pu être téléchargé. Utilisez JPG, PNG ou WebP (max. 5 Mo).',
        };

        throw ValidationException::withMessages([$field => $message]);
    }

    public static function storeFromRequest(Request $request, string $field, string $directory): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return self::storeUploadedFile($request->file($field), $directory);
    }
}
