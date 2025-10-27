<?php

namespace CourseRegistrationLite\Controllers;

use App\CustomClasses\zarinPal;
use App\Http\Controllers\Controller;
use CourseRegistrationLite\Jobs\SendWorkshopRegistrationSmsJob;
use CourseRegistrationLite\Models\WorkshopRegistration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkshopRegistrationController extends Controller
{
    public function showForm()
    {
        $courses = config('course-registration-lite.courses', []);

        return view('CourseRegistrationLiteViews::index', [
            'courses' => $courses,
        ]);
    }

    public function submitForm(Request $request)
    {
        $courseKeys = array_keys(config('course-registration-lite.courses', []));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => 'required|numeric|digits:10',
            'birth_certificate_number' => 'required|numeric|digits_between:1,10',
            'birth_date' => 'required|date',
            'mobile' => 'required|numeric|digits:11',
            'phone' => 'required|numeric|digits_between:8,11',
            'course' => ['required', 'string', Rule::in($courseKeys)],
        ]);

        $course = config('course-registration-lite.courses.' . $validated['course']);

        $registration = WorkshopRegistration::create([
            'name' => $validated['name'],
            'national_id' => $validated['national_id'],
            'birth_certificate_number' => $validated['birth_certificate_number'],
            'birth_date' => $validated['birth_date'],
            'mobile' => $validated['mobile'],
            'phone' => $validated['phone'],
            'course_key' => $validated['course'],
            'course_title' => $course['title'],
            'price' => $course['price'],
        ]);

        $callbackUrl = route('workshop-registration.verify');
        $description = sprintf(
            'پرداخت هزینه دوره %s به مبلغ %s تومان توسط %s با کدملی %s',
            $course['title'],
            number_format($course['price']),
            $registration->name,
            $registration->national_id
        );

        $authorityCode = zarinPal::getAuthority($course['price'], $description, $registration->mobile, $callbackUrl);

        $registration->update([
            'authority' => $authorityCode,
            'status' => 'pending',
        ]);

        return redirect(config('zarinpal.pay_url') . $authorityCode);
    }

    public function verify(Request $request)
    {
        $registration = WorkshopRegistration::where('authority', $request->Authority)->firstOrFail();
        $result = zarinPal::verify($request, $registration->price);

        if ($result === 0 || $result === 1) {
            $registration->update([
                'status' => 'failed',
            ]);
        } else {
            SendWorkshopRegistrationSmsJob::dispatch(
                $registration->mobile,
                $registration->name,
                $registration->course_title,
                $registration->price
            );

            $registration->update([
                'ref_id' => $result,
                'status' => 'success',
            ]);
        }

        return view('CourseRegistrationLiteViews::verify', ['refId' => $result]);
    }
}
