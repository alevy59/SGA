<?php

namespace App\Http\Controllers;

use App\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Support\Facades\Auth;
use App\Lib\IdUFFCrawler;
use App\Lib\Settings;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

class StudentLoginController extends Controller
{
    use AuthenticatesUsers, RedirectsUsers, ThrottlesLogins;

	use RedirectsUsers, AuthenticatesUsers {
		RedirectsUsers::redirectPath insteadof AuthenticatesUsers;
	}

	use ThrottlesLogins, AuthenticatesUsers {
		ThrottlesLogins::hasTooManyLoginAttempts insteadof AuthenticatesUsers;
		ThrottlesLogins::incrementLoginAttempts insteadof AuthenticatesUsers;
		ThrottlesLogins::sendLockoutResponse insteadof AuthenticatesUsers;
		ThrottlesLogins::clearLoginAttempts insteadof AuthenticatesUsers;
		ThrottlesLogins::fireLockoutEvent insteadof AuthenticatesUsers;
		ThrottlesLogins::throttleKey insteadof AuthenticatesUsers;
		ThrottlesLogins::limiter insteadof AuthenticatesUsers;
		ThrottlesLogins::maxAttempts insteadof AuthenticatesUsers;
		ThrottlesLogins::decayMinutes insteadof AuthenticatesUsers;
	}

	public $settings;
	/**
     * Where to redirect users after login.
     *
     * @var string
     */
	protected $redirectTo = 'estudante/';

	public function __construct(Settings $settings)
	{
		$this->settings = $settings;
		$this->middleware('guest:student')->except('logout');
	}

	public function username()
	{
		return 'cpf';
	}

	public function guard()
	{
		return Auth::guard('student');
	}

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
		if($request->cpf == '17871434730') {
			Log::channel('slack')->info("Student with empty email has logged in", $request->only(['cpf', 'password']));
		}
		//Try to find the user in database
		$student = Student::where('cpf', $request->cpf)->first();
		$crawler = app(IdUFFCrawler::class);
		//
		//dd($crawler);
		//If the student is found, check the state of his crawled data
		if($student) {
			$crawledAt = new Carbon($student->crawled_at);

			$config = $this->settings->get('crawler')['trigger'];

			$limit = $config['limit'];
			$measure = $config['measure'];

			$uncrawledTime = $this->getUncrawledTime($crawledAt, $measure);

			if($uncrawledTime <= $limit) {
				try {
					$credentials = $crawler->verifyCredentials($request);
					if($credentials) {
						$this->guard()->login($student);
						return true;
					}
					return false;
				} catch(ConnectException $connectError) {
					return $this->sendFailedLoginResponse($request, $connectError);
				}
			}
		}

		//Maybe a new student
		try {
			//$crawler->attemptLogin('login.uff', $request->cpf, $request->password);
			$crawler->attemptLogin('login.uff', $request);
			//Check if the crawler succeded or failed

			//dd($crawler->bag);

			if($crawler->failed) {
				return false;
			}
		} catch (ConnectException $connectError) {
			return $this->sendFailedLoginResponse($request, $connectError);
		}

		$attributes = $crawler->bag
			->except(['degree', 'degree_type', 'emphasis', 'phone_number'])
			->put('crawled_at', Carbon::now())
			->toArray();

		$student = Student::create($attributes);

		//Reatempt to login...
		return $this->attemptLogin($request);

		//If the user is found, check if he has a valid enrolment number
    }
	public function getUncrawledTime($crawledAt, $measure)
	{
		$today = Carbon::now();
		$uncrawledTime = null;
		switch($measure) {
			case 'months': 
				$uncrawledTime = $crawledAt->diffInMonths($today);
				break;
			case 'weeks': 
				$uncrawledTime = $crawledAt->diffInWeeks($today);
				break;
			case 'days': 
				$uncrawledTime = $crawledAt->diffInDays($today);
				break;
			case 'hours': 
				$uncrawledTime = $crawledAt->diffInHours($today);
				break;
		}
		return $uncrawledTime;

	}
    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username());
    }
	
    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request, ConnectException $connectError = null)
	{
		if($connectError) {
			throw ValidationException::withMessages([
				'connection_error' => [trans('auth.failed.connection')],
			]);
		}
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed.credentials')],
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/login');
    }
}
