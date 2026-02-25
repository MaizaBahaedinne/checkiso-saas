<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

/**
 * Handles locale switching.
 * Route: GET /lang/{locale}
 */
class LangController extends BaseController
{
    public function switch(string $locale): \CodeIgniter\HTTP\RedirectResponse
    {
        if (in_array($locale, ['fr', 'en'])) {
            session()->set('lang', $locale);

            // Persist to DB if the user is authenticated
            if ($userId = session()->get('user_id')) {
                $userModel = new \App\Models\UserModel();
                $userModel->update($userId, ['lang_preference' => $locale]);
            }
        }

        // Redirect back to the referring page, or to dashboard as fallback
        $referer = $this->request->getServer('HTTP_REFERER');
        if ($referer && str_starts_with($referer, base_url())) {
            return redirect()->to($referer);
        }

        return redirect()->to(site_url('dashboard'));
    }
}
