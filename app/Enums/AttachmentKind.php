<?php

namespace App\Enums;

enum AttachmentKind: string
{
    case Image = 'image';
    case Pdf = 'pdf';

    /**
     * Resolve the attachment kind from a MIME type.
     */
    public static function fromMimeType(string $mimeType): self
    {
        return $mimeType === 'application/pdf' ? self::Pdf : self::Image;
    }
}
