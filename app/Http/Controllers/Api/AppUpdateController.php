<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppUpdateController extends Controller
{
    public function status()
    {
        $isActive = AppSetting::getValue('app_update_active', '0') === '1';

        return response()->json([
            'is_updating' => $isActive,
            'title'       => 'AuraCinema නව Update එකක්!',
            'message'     => 'මේ වෙලාවේ App එකට අලුත් update එකක් නිකුත් වෙමින් පවතී. App එක open කරගෙන තත්පර 10ක් පමණ තියලා, සම්පූර්ණයෙන්ම close කරලා ආපහු open කළාම අලුත් update එක ලැබේවි.',
        ]);
    }

    public function toggleWeb(Request $request)
    {
        $current = AppSetting::getValue('app_update_active', '0') === '1';
        $next = $current ? '0' : '1';
        AppSetting::setValue('app_update_active', $next);

        return back()->with(
            'success',
            $next === '1'
                ? 'Mobile App Update Notification එක ON කළා!'
                : 'Mobile App Update Notification එක OFF කළා.'
        );
    }
}