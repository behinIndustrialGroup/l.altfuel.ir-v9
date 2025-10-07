<?php

namespace CourseRegistration\Controllers;

use App\CustomClasses\zarinPal;
use App\Http\Controllers\Controller;
use CourseRegistration\Jobs\SendCourseRegistrationSmsJob;
use CourseRegistration\Models\CourseRegistration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseRegistrationController extends Controller
{
    public function showForm()
    {
        $courses = config('course-registration.courses', []);

        return view('CourseRegistrationViews::index', [
            'courses' => $courses,
        ]);
    }

    public function submitForm(Request $request)
    {
        $courseKeys = array_keys(config('course-registration.courses', []));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => 'required|numeric|digits:10',
            'mobile' => 'required|numeric|digits:11',
            'course' => ['required', 'string', Rule::in($courseKeys)],
        ]);

        $course = config('course-registration.courses.' . $validated['course']);

        $registration = CourseRegistration::create([
            'name' => $validated['name'],
            'national_id' => $validated['national_id'],
            'mobile' => $validated['mobile'],
            'course_key' => $validated['course'],
            'course_title' => $course['title'],
            'price' => $course['price'],
        ]);

        $callbackUrl = route('course-registration.verify');
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
        $registration = CourseRegistration::where('authority', $request->Authority)->firstOrFail();
        $result = zarinPal::verify($request, $registration->price);

        if ($result === 0 || $result === 1) {
            $registration->update([
                'status' => 'failed',
            ]);
        } else {
            SendCourseRegistrationSmsJob::dispatch(
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

        return view('CourseRegistrationViews::verify', ['refId' => $result]);
    }
}
