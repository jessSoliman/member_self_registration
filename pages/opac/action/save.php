<?php
use SLiMS\DB;
use SLiMS\Plugins;
use SLiMS\Filesystems\Storage;
use SLiMS\Captcha\Factory as Captcha;
use Volnix\CSRF\CSRF;

defined('INDEX_AUTH') or die('Direct access is not allowed!');

if (isset($_POST['form'])) {
    try {
        $structure = json_decode($schema->structure, true);
        $option = json_decode($schema->option ?? '');

        $passwordFieldId = @array_pop(array_keys(array_filter($structure, function($data) {
            return $data['field'] === 'mpasswd';
        })));

        if (!CSRF::validate($_POST)) {
            throw new Exception(__('Invalid form submission.'));
        }

        // CAPTCHA
        $captcha = Captcha::section('memberarea');
        if (($option?->captcha ?? false) && $captcha->isSectionActive() && !$captcha->isValid()) {
            $message = isDev() ? $captcha->getError() : __('Wrong Captcha Code entered, Please write the right code!');
            session_unset();
            throw new Exception($message);
        }

        // Password check
        if (isset($_POST['form'][$passwordFieldId]) && $_POST['form'][$passwordFieldId] !== $_POST['confirm_password']) {
            throw new Exception("Password does not match.");
        }

        // ✅ Check if user already exists
        $member_id = $_POST['form']['member_id'] ?? null;
        $member_email = $_POST['form']['member_email'] ?? null;

        if ($member_id || $member_email) {
            $params = [];
            $conditions = [];

            if ($member_id) {
                $conditions[] = 'member_id = ?';
                $params[] = $member_id;
            }

            if ($member_email) {
                $conditions[] = 'member_email = ?';
                $params[] = $member_email;
            }

            $sql = 'SELECT COUNT(*) FROM member WHERE ' . implode(' OR ', $conditions);
            $stmt = DB::getInstance()->prepare($sql);
            $stmt->execute($params);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception('A member with this ID or Email already exists.');
            }
        }

        // Prepare insert
        $sqlSet = [];
        $sqlParams = [];
        $sqlRaw = 'INSERT INTO self_registration_' . trim(strtolower(str_replace(' ', '_', $schema->name))) . ' SET ';

        foreach ($_POST['form'] as $order => $value) {
            $detail = $structure[$order];

            if ($detail['field'] === 'advance') {
                if (in_array($detail['advfieldtype'], ['enum','enum_radio','text_multiple'])) {
                    $field = explode(',', $detail['advfield']);
                    $detail['field'] = $field[0];
                } else {
                    $detail['field'] = $detail['advfield'];
                }
            }

            $sqlSet[] = '`' . $detail['field'] . '` = ?';

            if ($detail['field'] === 'mpasswd') {
                $value = password_hash($value, PASSWORD_BCRYPT);
            }

            if (is_array($value)) $value = json_encode($value);

            $sqlParams[] = $value;
        }

        // ✅ Image upload if enabled
        if (($option?->image ?? false)) {
            if ($_FILES['member_image']['error'] == 1) {
                $max = ini_get('upload_max_filesize');
                throw new Exception("Profile image is too large. Max allowed is {$max}.");
            }

            if (!empty($_FILES['member_image']) && $_FILES['member_image']['size']) {
                $images_disk = Storage::images();
                $newFilename = md5(rand(1, 1000) . date('this'));

                $image_upload = $images_disk->upload('member_image', function($images) use($sysconf) {
                    $images->isExtensionAllowed($sysconf['allowed_images']);
                    $images->isLimitExceeded(500 * 1024);
                    if (!empty($images->getError())) $images->destroyIfFailed();
                })->as('persons' . DS . $newFilename);

                if ($image_upload->getUploadStatus()) {
                    $sqlSet[] = '`member_image` = ?';
                    $sqlParams[] = $image_upload->getUploadedFileName();
                } else {
                    throw new Exception('Failed to upload image: ' . $image_upload->getError());
                }
            }
        }

        $sqlSet[] = '`created_at` = now()';
        $query = $sqlRaw . implode(',', $sqlSet);

        Plugins::getInstance()->execute('member_self_before_save', [
            'query' => $query,
            'sqlParams' => $sqlParams
        ]);

        $insert = DB::getInstance()->prepare($query);
        $insert->execute($sqlParams);

        if ($insert->rowCount() > 0) {
            // ✅ Redirect with success message (flash)
            redirect()->withMessage('self_regis_success', $option->message_after_save ?: 'Registration successful!')->to(pluginUrl(['section' => 'form']));
        } else {
            throw new Exception('Failed to save. Please try again.');
        }

    } catch (Exception $e) {
        redirect()->withMessage('self_regis_error', $e->getMessage())->back();
    }
}
