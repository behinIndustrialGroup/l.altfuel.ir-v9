<?php

namespace CourseRegistration\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mkhodroo\SmsTemplate\Controllers\SendSmsController;

class SendCourseRegistrationSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $mobile;
    protected string $name;
    protected string $courseTitle;
    protected int $price;

    public function __construct(string $mobile, string $name, string $courseTitle, int $price)
    {
        $this->mobile = $mobile;
        $this->name = $name;
        $this->courseTitle = $courseTitle;
        $this->price = $price;
    }

    public function handle(): void
    {
        $formattedPrice = number_format($this->price);
        $message = sprintf(
            '%s با شماره %s در دوره "%s" با مبلغ %s تومان ثبت نام کرد.',
            $this->name,
            $this->mobile,
            $this->courseTitle,
            $formattedPrice
        );

        $smsSender = new SendSmsController();
        foreach (config('course-registration.notify_numbers', []) as $number) {
            $smsSender->send($number, $message);
        }
    }
}
