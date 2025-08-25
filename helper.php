<?php
use SLiMS\Url;
use SLiMS\Captcha\Factory as Captcha;
use SLiMS\Filesystems\Storage;

if (!function_exists('getActiveSchemaData'))
{
    function getActiveSchemaData()
        {
            $state = \SLiMS\DB::getInstance()->query(
                'SELECT * FROM self_registration_schemas WHERE status = 1'
            );

            return $state->rowCount() ? $state->fetchAll(PDO::FETCH_OBJ) : [];
        }


}

if (!function_exists('action')) {
    function action(string $actionName, array $attribute = [])
    {
        global $sysconf;
        extract($attribute);
        $trace = debug_backtrace(limit: 1);
        $info = pathinfo(array_pop($trace)['file']);
        
        if (file_exists($path = $info['dirname'] . DS . 'action' . DS . basename($actionName) . '.php')) {
            include $path;
        } else {
            throw new Exception('Action ' . $actionName . ' is not found!', 404);
        }
    }
}


if (!function_exists('pluginUrl'))
{
    /**
     * Generate URL with plugin_container.php?id=<id>&mod=<mod> + custom query
     *
     * @param array $data
     * @param boolean $reset
     * @return string
     */
    function pluginUrl(array $data = [], bool $reset = false): string
    {
        // back to base uri
        if ($reset) return Url::getSelf(fn($self) => $self . '?mod=' . $_GET['mod'] . '&id=' . $_GET['id']);
        
        return Url::getSelf(function($self) use($data) {
            return $self . '?' . http_build_query(array_merge($_GET,$data));
        });
    }
}

if (!function_exists('textColor')) {
    // source : https://www.bitbook.io/php-function-to-calculate-the-best-font-color-for-a-background-color/
    function textColor($hexCode){
        $redHex = substr($hexCode,0,2);
        $greenHex = substr($hexCode,2,2);
        $blueHex = substr($hexCode,4,2);
    
        $r = (hexdec($redHex)) / 255;
        $g = (hexdec($greenHex)) / 255;
        $b = (hexdec($blueHex)) / 255;
    
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        // human eye sees 60% easier
        if($brightness > .6){
            return '000000';
        }else{
            return 'ffffff';
        }
    }
}

if (!function_exists('formGenerator'))
{
    /**
     * Form generator based on schema
     *
     * @param [type] $data
     * @param array $record
     * @param string $actionUrl
     * @param [type] $opac
     * @return void
     */
    function formGenerator($data, $record = [], $actionUrl = '', $opac = null)
    {
        $structure = json_decode($data->structure, true);
        $option = json_decode($data->option??'');
        $info = json_decode($data->info);

        ob_start();

        // Start form
        $js = '';
        $withUpload = '';
        if (($option?->image??false)) $withUpload = 'enctype="multipart/form-data"';

        echo '<form id="self_member" method="POST" action="' . $actionUrl . '" ' . $withUpload . '>';

        if ($success = flash()->includes('self_regis_success')) {
                flash()->success($success);
            }

        // set error
        if ($key = flash()->includes('self_regis_error'))
        {
            flash()->danger($key);
        }

        // set action url
        if ($actionUrl === '' || stripos($actionUrl, 'admin') !== false) {
            if ($actionUrl === '') {
                echo '<h3>Preview</h3>'; // Pratinjau -> Preview
                echo '<h5>Scheme ' . $data->name . '</h5>'; // Skema -> Scheme
            } else {
                echo '<h3>New Member</h3>'; // Pratinjau Data -> Data Preview
                echo '<h5>'.$record['member_name'].'</h5>';
            }
        } else {
            if ($opac !== null) $opac->page_title = $info->title;
            $descInfo = '<div class="alert alert-info p-3">' . strip_tags($info->desc, '<p><a><i><em><h1><h2><h3><ul><ol><li>') . '</div>';
        }        

        if ($info->position == 'top' && isset($descInfo)) {
            echo $descInfo;
        }

        // Generate form structure
        foreach ($structure as $key => $column) {
            // Convert key to fieldname
            $isAdminView = strpos($actionUrl, 'admin');
            if ($isAdminView) { 
                if (empty($column['advfield'])) {
                    $key = $column['field'];
                } else {
                    $advfield = explode(',', $column['advfield']);
                    $key = $advfield[0];
                }
            }

            // determine mandatory of the element
            $is_required = $column['is_required'] === true ? ' required' : '';

            // Set label element
            $required_mark = $is_required ? '<em class="text-danger">*</em>' : '';
            echo <<<HTML
            <div class="my-3">
                <label class="form-label"><strong>{$column['name']} {$required_mark}</strong></label>
            HTML;

            // Get default value
            $defaultValue = $record[$column['field']]??$record[$column['advfield']]??'';

            // special condition of some field type
            if (in_array($column['advfieldtype'], ['enum','enum_radio','text_multiple'])) {
                list($name, $detail) = explode(',', $column['advfield']);
                $defaultValue = $record[$name]??'';
            }
    
            // set html form element based on database field
            switch ($column['field']) {
                case 'mpasswd':
                    if (!empty($actionUrl)) {
                    if (strpos($actionUrl, 'admin') !== false) {
                            echo '<br>The password is hidden to prevent changes to what the member originally set.';
                            break;
                        }

                        $is_required = '';
                    }
                    echo <<<HTML
                    <br>
                    <ul id="password-rules" style="font-size: small; list-style: none; padding-left: 1em; margin-bottom: 5px;">
                        <li id="rule-length" style="color: red;">• At least 8 characters</li>
                        <li id="rule-uppercase" style="color: red;">• At least one uppercase letter</li>
                        <li id="rule-lowercase" style="color: red;">• At least one lowercase letter</li>
                        <li id="rule-number" style="color: red;">• At least one number</li>
                    </ul>

                    <small>New Password</small>
                    <div style="position: relative;">
                        <input type="password" 
                            placeholder="Enter your {$column['name']}" 
                            name="form[{$key}]" 
                            id="pass1" 
                            class="form-control pr-5" 
                            {$is_required}>
                        <button type="button" class="toggle-password" data-target="pass1" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none;">
                            🤔
                        </button>
                    </div>

                    <ul style="font-size: small; list-style: none; padding-left: 1em; margin-top: 10px; margin-bottom: 5px;">
                        <li id="rule-match" style="color: red;">• Passwords must match</li>
                    </ul>

                    <small>Retype Password</small>
                    <div style="position: relative;">
                        <input type="password" 
                            name="confirm_password" 
                            placeholder="Re-enter your {$column['name']}" 
                            id="pass2" 
                            class="form-control pr-5" 
                            {$is_required}>
                        <button type="button" class="toggle-password" data-target="pass2" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none;">
                            🤔
                        </button>
                    </div>

                    <script>
                    const pass1 = document.getElementById('pass1');
                    const pass2 = document.getElementById('pass2');

                    const rules = {
                        length: document.getElementById('rule-length'),
                        uppercase: document.getElementById('rule-uppercase'),
                        lowercase: document.getElementById('rule-lowercase'),
                        number: document.getElementById('rule-number'),
                        match: document.getElementById('rule-match')
                    };

                    function updatePasswordRules() {
                        const val1 = pass1.value;
                        const val2 = pass2.value;

                        const hasLength = val1.length >= 8;
                        const hasUpper = /[A-Z]/.test(val1);
                        const hasLower = /[a-z]/.test(val1);
                        const hasNumber = /[0-9]/.test(val1);
                        const isMatch = val1 === val2 && val1 !== '';

                        rules.length.style.color = hasLength ? 'green' : 'red';
                        rules.uppercase.style.color = hasUpper ? 'green' : 'red';
                        rules.lowercase.style.color = hasLower ? 'green' : 'red';
                        rules.number.style.color = hasNumber ? 'green' : 'red';
                        rules.match.style.color = isMatch ? 'green' : 'red';

                        return hasLength && hasUpper && hasLower && hasNumber && isMatch;
                    }

                    pass1.addEventListener('input', updatePasswordRules);
                    pass2.addEventListener('input', updatePasswordRules);

                    document.querySelector('form').addEventListener('submit', function(e) {
                        if (!updatePasswordRules()) {
                            e.preventDefault();
                            alert("Please meet all password requirements.");
                        }
                    });

                    // Show/hide password toggle
                    document.querySelectorAll('.toggle-password').forEach(btn => {
                        btn.addEventListener('click', function () {
                            const targetId = this.getAttribute('data-target');
                            const input = document.getElementById(targetId);
                            if (input.type === 'password') {
                                input.type = 'text';
                                this.textContent = '🫣'; // change icon
                            } else {
                                input.type = 'password';
                                this.textContent = '🤔';
                            }
                        });
                    });
                    </script>
                    HTML;
                    break;



            
                case 'gender':
                    $man = $defaultValue != 1 ?:'selected';
                    $woman = $defaultValue != 0 ?:'selected';
                    echo <<<HTML
                    <select name="form[{$key}]" class="form-control" {$is_required}>
                        <option>Select</option> <!-- Pilih -->
                        <option value="1" {$man}>Male</option> <!-- Laki-Laki -> Male -->
                        <option value="0" {$woman}>Female</option> <!-- Perempuan -> Female -->
                    </select>
                    HTML;
                    break; 

                case 'inst_name':
                    $apalit = $defaultValue != 1 ?:'selected';
                    $caloocan = $defaultValue != 0 ?:'selected';
                    echo <<<HTML
                    <select name="form[{$key}]" class="form-control" {$is_required}>
                        <option>Select</option> <!-- Pilih -->
                        <option value="Apalit" {$apalit}>Apalit</option> <!-- Apalit -->
                        <option value="Caloocan" {$caloocan}>Caloocan</option> <!-- Caloocan -->
                    </select>
                    HTML;
                    break; 
            
                case 'member_address':
                    echo <<<HTML
                    <textarea name="form[{$key}]" placeholder="Enter your {$column['name']}" class="form-control" {$is_required}>{$defaultValue}</textarea>
                    HTML;
                    break;
            
                case 'member_type_id':
                    $memberType = \SLiMS\DB::getInstance()->query('select member_type_id, member_type_name from mst_member_type');
                    echo '<select class="form-control" name="form[' . $key . ']" ' . $is_required . '>';
                    echo '<option value="0">Select</option>'; // Pilih -> Select
                    while ($result = $memberType->fetch(PDO::FETCH_NUM)) {
                        echo '<option value="' . $result[0] . '" ' . ($defaultValue != $result[0] ?:'selected') . '>' . $result[1] . '</option>';
                    }
                    echo '</select>';
                    break;
            
                // Advance field element
                case 'advance':
                    switch ($column['advfieldtype']) {
                        // short text field
                        case 'varchar':
                        case 'int':
                            $types = ['varchar' => 'text', 'int' => 'number'];
                            $type = $types[$column['advfieldtype']];
                            echo <<<HTML
                            <input type="{$type}" name="form[{$key}]" value="{$defaultValue}" placeholder="Enter your {$column['name']}" class="form-control" {$is_required}/>
                            HTML;
                            break;
            
                        // long text
                        case 'text':
                            echo <<<HTML
                            <textarea name="form[{$key}]" placeholder="Enter your {$column['name']}" class="form-control" {$is_required}>{$defaultValue}</textarea>
                            HTML;
                            break;
            
                        // select list
                        case 'enum':
                            list($field,$list) = explode(',', $column['advfield']);
                            echo '<select name="form[' . $key . ']" class="form-control" '.$defaultValue.'>';
                            echo '<option value="">Select</option>'; // Pilih -> Select
                            $selected = '';
                            foreach (explode('|', $list) as $item) {
                                if ($defaultValue == $item) $selected = 'selected';
                                echo '<option value="'.$item.'" '.$selected.'>' . $item . '</option>';
                                $selected = '';
                            }
                            echo '</select>';
                            break;
            
                        // Select list as radio button
                        case 'enum_radio':
                            $field = explode(',', $column['advfield']);
                            $uniqueId = md5($field[0]);
                            $checked = '';
            
                            if ($is_required) {
                                $js .= <<<HTML
                                if ($('.radio{$uniqueId}:checked').length < 1) {
                                    evt.preventDefault();
                                    alert('Select one of the {$column['name']} options'); // Pilih salah satu dari isian
                                    return;
                                }
                                HTML;
                            }
            
                            echo '<div class="d-flex flex-column">';
                            foreach (explode('|', trim($field[1])) as $optionKey => $value) {
                                if (empty($value)) continue;
                                if ($defaultValue == $value) $checked = 'checked';
                                echo '<div>
                                    <input class="radio'.$uniqueId.'" id="radio' . $uniqueId . '-' . $optionKey . '" data-title="' . $column['name'] . '" type="radio" name="form[' . $key . ']" value="' . $value . '" ' . $checked . '/>
                                    <label for="radio' . $uniqueId . '-' . $optionKey . '" style="cursor: pointer">' . $value . '</label>
                                </div>';
                            }
                            echo '</div>';
                            break;
            
                        // multiple choice data
                        case 'text_multiple':
                            $field = explode(',', $column['advfield']);
                            $uniqueId = md5($field[0]);
                            $defaultValue = json_decode(trim($defaultValue), true);
                            $checked = '';
            
                            if ($is_required) {
                                $js .= <<<HTML
                                if ($('.checkbox{$uniqueId}:checked').length < 1) {
                                    evt.preventDefault();
                                    alert('Select at least one of the {$column['name']} options'); // Pilih salah satu dari isian
                                    return;
                                }
                                HTML;
                            }
            
                            echo '<div class="d-flex flex-column">';
                            foreach (explode('|', trim($field[1])) as $optionKey => $value) {
                                // if (empty($value)) continue;
                                if (in_array($value, $defaultValue??[])) $checked = 'checked';
                                echo '<div class="mx-3">
                                    <input class="checkbox'.$uniqueId.'" id="checkbox' . $uniqueId . '-' . $optionKey . '" type="checkbox" name="form[' . $key . '][]" value="' . $value . '" ' . $checked . '/>
                                    <label for="checkbox' . $uniqueId . '-' . $optionKey . '" style="cursor: pointer">' . $value . '</label>
                                </div>';
                                $checked = '';
                            }
                            echo '</div>';
                            break;
                    }
                    break;
            
                // Image cover
                case 'member_image':
                    if (($option?->image ?? null) === null) {
                        echo '<div class="alert alert-info font-weight-bold">You have not set this field in "Form Settings"</div>';
                    } else {
                        if (!isset($record['member_image'])) {
                            echo <<<HTML
                                <input 
                                    type="file" 
                                    name="member_image" 
                                    accept="image/jpeg, image/png, image/jpg, image/webp" 
                                    placeholder="Enter your {$column['name']}" 
                                    class="form-control d-block" 
                                    {$is_required} 
                                />
                                <small>Maximum photo file size is 2MB. Allowed types: jpg, jpeg, png, webp.</small>
                            HTML;
                        } else {
                            $filename = basename($record['member_image']); // prevent path traversal
                            $safeImage = Storage::images()->isExists('persons/' . $filename) ? $filename : 'avatar.jpg';

                            // escape output
                            $imageUrl = htmlspecialchars(SWB . 'lib/minigalnano/createthumb.php?filename=images/persons/' . rawurlencode($safeImage) . '&width=120');

                            echo '<img class="d-block" src="' . $imageUrl . '" alt="Member Image" />';
                        }
                    }
                    break;

                case 'member_email':
                    if ($actionUrl !== '') {
                        $is_required = '';
                    }
                    echo <<<HTML
                    <br>
                    <input type="email"
                        value="{$defaultValue}"
                        placeholder="Enter your {$column['name']}" 
                        name="form[{$key}]" 
                        id="member_email" 
                        class="form-control" 
                        {$is_required} 
                        pattern="^[a-zA-Z0-9._%+\-]+@(student\\.)?laverdad\\.edu\\.ph$"
                        title="Only laverdad.edu.ph or student.laverdad.edu.ph emails are allowed">

                    <div id="email-error" style="color:red; font-size:small;"></div>

                    <script>
                    const emailInput = document.getElementById('member_email');
                    const emailError = document.getElementById('email-error');

                    function validateEmail() {
                        const value = emailInput.value.trim();
                        const regex = /^[a-zA-Z0-9._%+-]+@(student\\.)?laverdad\\.edu\\.ph$/;
                        if (value === '') {
                            emailError.textContent = '';
                            return true;
                        }
                        if (!regex.test(value)) {
                            emailError.textContent = "Only laverdad.edu.ph or student.laverdad.edu.ph emails are allowed.";
                            return false;
                        } else {
                            emailError.textContent = "";
                            return true;
                        }
                    }

                    emailInput.addEventListener('input', validateEmail);

                    document.querySelector('form').addEventListener('submit', function(e) {
                        if (!validateEmail()) {
                            e.preventDefault();
                        }
                    });
                    </script>
                    HTML;
                    break;
            
                // Generate as input type text, date, or email
                default:
                    $types = ['birth_date' => 'date'];
                    $type = isset($types[$column['field']]) ? $types[$column['field']] : 'text';
                    echo <<<HTML
                    <input type="{$type}" name="form[{$key}]" value="{$defaultValue}" placeholder="Enter your {$column['name']}" class="form-control" {$is_required}/>
                    HTML;
                    break;
            }

            echo <<<HTML
            </div>
            HTML;
        }

        if ($info->position == 'bottom' && isset($descInfo)) {
            echo $descInfo;
        }

        if (($option?->with_agreement??false) && strpos($actionUrl, 'admin') === false) {
            echo <<<HTML
            <div>
                <input type="checkbox" id="iAgree"/>
                <label for="iAgree" style="cursor: pointer">I agree to the above prerequisites</label> <!-- Saya menyetujui prasyarat diatas -->
            </div>
            HTML;    
        }        

        // set form action url
        if ($actionUrl !== '') {
            // Captcha initialize
            $captcha = Captcha::section('memberarea');
        
            // public area
            if (strpos($actionUrl, 'admin') === false) {
                if (($option?->captcha??false) && $captcha->isSectionActive() && config('captcha', false)) 
                {
                    echo '<div class="captchaMember my-2">';
                    echo $captcha->getCaptcha();
                    echo '</div>';
                }
        
                echo \Volnix\CSRF\CSRF::getHiddenInputString();
        
                $disableBeforeAgree = '';
                if ($option?->with_agreement??false) $disableBeforeAgree = 'disabled';
        
                echo '<div class="form-group">
                    <input type="hidden" name="action" value="save"/>
                    <button class="btn btn-primary" type="submit" name="save" '.$disableBeforeAgree.' ' . (empty($disableBeforeAgree) ? '' : 'title="Click \'I agree to the above prerequisites\'"') . '>Register</button> <!-- Daftar -> Register -->
                    <button class="btn btn-outline-secondary" type="reset" name="save">Cancel</button> <!-- Batal -> Cancel -->
                </div>
                ';
            } else {
                echo '<div class="form-group">
                    <input type="hidden" name="action" value="acc"/>
                    <button class="btn btn-success" type="submit" name="acc">Approve 1</button> <!-- Setujui -> Approve -->
                    <a class="btn btn-danger" href="' .  pluginUrl(['section' => 'view_detail', 'member_id' => $_GET['member_id']??0, 'headless' => 'yes', 'action' => 'delete_reg']) . '">Delete</a> <!-- Hapus -> Delete -->
                </div>';
            }
            // if (strpos($actionUrl, 'admin') === false) {
            //     echo '<strong><em class="text-danger">*</em> ) required field</strong>';
            // }

            

        }
        
        echo '</form>';

        // Custom JS
        if (strpos($actionUrl, 'admin') === false) {
            $agreeJs = '';
            if ($option?->with_agreement??false) {
                $agreeJs = <<<HTML
                $('#iAgree').click(function() {
                    if ($('#iAgree:checked').length < 1) { 
                        $('button[name="save"]').prop('disabled', true)
                        $('button[name="save"]').prop('title', 'Klik \'Saya menyetujui prasyarat diatas\'')
                    } else {
                        $('button[name="save"]').prop('title', 'Klik untuk menyimpan data')
                        $('button[name="save"]').prop('disabled', false)
                    }
                });
                HTML;
            }
            echo <<<HTML
            <script>
                $(document).ready(function() {
                    {$agreeJs}
                    $('#self_member').submit(function(evt) {
                        {$js}
                    })
                })
            </script>
            HTML;
        }
        return ob_get_clean();
    }
}