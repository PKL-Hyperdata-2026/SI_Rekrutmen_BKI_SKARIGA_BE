<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Collection;

class MessageTemplateService
{
    public function listTemplates(): Collection
    {
        return MessageTemplate::orderBy('id')->get();
    }

    public function find(int $id): MessageTemplate
    {
        return MessageTemplate::findOrFail($id);
    }

    public function update(int $id, array $data, int $adminUserId): MessageTemplate
    {
        $template = MessageTemplate::findOrFail($id);
        $data['updated_by'] = $adminUserId;

        $template->update($data);

        return $template->fresh();
    }

    public function render(string $code, array $params = []): ?array
    {
        $template = MessageTemplate::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        $subject = $template->subject;
        $body = $template->body;

        foreach ($params as $key => $val) {
            $strVal = is_scalar($val) ? (string) $val : '';
            $subject = str_replace('{'.$key.'}', $strVal, $subject);
            $body = str_replace('{'.$key.'}', $strVal, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
