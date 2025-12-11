<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Enums\Notification\ChannelType;
use App\Enums\Notification\DispatchType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $channels = $this->input('channels', []);
            $recipient = $this->input('recipient', []);

            foreach (ChannelType::fromValues($channels) as $channel) {
                foreach ($channel->requiredRecipientFields() as $field) {
                    if (empty($recipient[$field] ?? null)) {
                        $validator->errors()->add(
                            "recipient.{$field}",
                            "{$channel->label()} 채널 사용 시 recipient.{$field}는 필수입니다."
                        );
                    }
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'dispatch_type' => ['required', 'string', Rule::enum(DispatchType::class)],
            'type' => ['required', 'string', 'max:100'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'string', Rule::enum(ChannelType::class)],
            'recipient' => ['required', 'array'],
            'recipient.email' => ['nullable', 'email'],
            'recipient.phone' => ['nullable', 'string'],
            'recipient.slack_webhook' => ['nullable', 'url', 'starts_with:https://hooks.slack.com/'],
            'payload' => ['required', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now', 'required_if:dispatch_type,scheduled'],
            'batch_key' => ['nullable', 'string', 'max:100', 'required_if:dispatch_type,batched'],
            'batch_window' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'dispatch_type.required' => '발송 유형은 필수입니다.',
            'dispatch_type.enum' => '유효한 발송 유형이 아닙니다. (immediate, scheduled, batched)',
            'type.required' => '알림 유형은 필수입니다.',
            'channels.required' => '채널은 필수입니다.',
            'channels.min' => '최소 하나의 채널을 선택해야 합니다.',
            'channels.*.enum' => '유효한 채널이 아닙니다. (email, sms, slack)',
            'recipient.required' => '수신자 정보는 필수입니다.',
            'recipient.email.email' => '유효한 이메일 주소를 입력해주세요.',
            'recipient.slack_webhook.url' => '유효한 Slack webhook URL을 입력해주세요.',
            'recipient.slack_webhook.starts_with' => 'Slack webhook URL은 https://hooks.slack.com/으로 시작해야 합니다.',
            'payload.required' => '알림 데이터는 필수입니다.',
            'scheduled_at.after' => '예약 시간은 현재 시간 이후여야 합니다.',
            'scheduled_at.required_if' => '예약 발송에는 scheduled_at이 필요합니다.',
            'batch_key.required_if' => '묶음 발송에는 batch_key가 필요합니다.',
            'batch_window.min' => '묶음 간격은 최소 60초입니다.',
            'batch_window.max' => '묶음 간격은 최대 86400초(24시간)입니다.',
        ];
    }

    public function getDispatchType(): DispatchType
    {
        return DispatchType::from($this->input('dispatch_type'));
    }

    public function getChannels(): array
    {
        return $this->input('channels');
    }
}
