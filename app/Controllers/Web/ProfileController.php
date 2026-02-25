<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages the authenticated user's own profile.
 *
 * Routes:
 *   GET  /profile          → index()         — display profile page
 *   POST /profile/info     → updateInfo()    — update name + email
 *   POST /profile/password → updatePassword()— change password
 *   POST /profile/lang     → updateLang()    — save language preference
 */
class ProfileController extends BaseController
{
    private UserModel $userModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->userModel = new UserModel();
    }

    // -------------------------------------------------------------------------
    // GET /profile
    // -------------------------------------------------------------------------
    public function index()
    {
        $user = $this->userModel->find(session()->get('user_id'));

        return view('profile/index', [
            'title'      => lang('Profile.title'),
            'user'       => $user,
            'active_tab' => session()->getFlashdata('active_tab') ?? 'info',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /profile/info — update first name, last name, email
    // -------------------------------------------------------------------------
    public function updateInfo()
    {
        $userId = session()->get('user_id');

        if (! $this->validate([
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'email'      => "required|valid_email|is_unique[users.email,id,{$userId}]",
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('active_tab', 'info');
        }

        $this->userModel->update($userId, [
            'first_name' => trim($this->request->getPost('first_name')),
            'last_name'  => trim($this->request->getPost('last_name')),
            'email'      => trim($this->request->getPost('email')),
        ]);

        // Refresh session so navbar shows the new name immediately
        $user = $this->userModel->find($userId);
        session()->set([
            'user_name'  => UserModel::fullName($user),
            'user_email' => $user['email'],
        ]);

        return redirect()->to(site_url('profile'))
            ->with('success', lang('Profile.info_updated'))
            ->with('active_tab', 'info');
    }

    // -------------------------------------------------------------------------
    // POST /profile/password — change password
    // -------------------------------------------------------------------------
    public function updatePassword()
    {
        if (! $this->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]',
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('active_tab', 'password');
        }

        $user = $this->userModel->find(session()->get('user_id'));

        if (! password_verify($this->request->getPost('current_password'), $user['password_hash'])) {
            return redirect()->back()
                ->with('error', lang('Profile.wrong_password'))
                ->with('active_tab', 'password');
        }

        $this->userModel->update($user['id'], [
            'password_hash' => password_hash(
                $this->request->getPost('new_password'),
                PASSWORD_DEFAULT
            ),
        ]);

        return redirect()->to(site_url('profile'))
            ->with('success', lang('Profile.password_updated'))
            ->with('active_tab', 'password');
    }

    // -------------------------------------------------------------------------
    // POST /profile/lang — persist language preference to DB + session
    // -------------------------------------------------------------------------
    public function updateLang()
    {
        $lang = $this->request->getPost('lang');

        if (in_array($lang, ['fr', 'en'], true)) {
            session()->set('lang', $lang);
            $this->userModel->update(session()->get('user_id'), [
                'lang_preference' => $lang,
            ]);
        }

        return redirect()->to(site_url('profile'))
            ->with('success', lang('Profile.lang_updated'))
            ->with('active_tab', 'lang');
    }
}
