<?php

require_once 'business.php';

function index(&$model) {
    return 'index_view';
}

function weezer(&$model) {
    return 'weezer_view';
}

function radiohead(&$model) {
    return 'radiohead_view';
}

function haveanicelife(&$model) {
    return 'haveanicelife_view';
}

function fishmans(&$model) {
    return 'fishmans_view';
}

function gallery(&$model) {
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if($_POST['formtype'] === 'uploadimage') {
            $imageTitle = $_POST["imagetitle"];
            $imageAuthor = $_POST["imageauthor"];
            $imageWatermark = $_POST["watermark"];
            if(isset($_POST['view'])) {
                $imageView = $_POST['view'];
            }
            else {
                $imageView = 'public';
            }
            $image = $_FILES["image"];
            upload_image($imageTitle, $imageAuthor, $imageWatermark, $imageView, $image, $model, $error);
            $webpage = 'gallery';
            return error_redirect($webpage, $error);
        }
        else if($_POST['formtype'] === 'checkimages') {
            if(isset($_POST['check'])) {
                choose_images($_POST['check'], true);
            }
            return 'redirect:'.$_SERVER['HTTP_REFERER'];
        }
    }
    else {
        if(isset($_GET["error"])) {
            switch($_GET["error"]) {
                case "emptyinput":
                    $model['errorText'] = "Fill in all the fields!";
                    break;
                case "error":
                    $model['errorText'] = "An error has occurred!";
                    break;
                case "invalidimg":
                    $model['errorText'] = "Choose a proper picture!";
                    break;
                case "toobigimg":
                    $model['errorText'] = "The image is too big!";
                    break;
                case "success":
                    $model['errorText'] = "The image has been uploaded!";
                    break;
            }
        }

        if (isset($_SESSION['login'])) {
            $model["imageauthor"] = $_SESSION['login'];
        }
        else {
            $model["imageauthor"] = '';
        }

        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }
    
        $imagesPerPage = 4;
        $skip = ($page - 1) * $imagesPerPage;
    
        $images = get_images($skip, $imagesPerPage, $pages);
    
        $model['images'] = $images;
        $model['page'] = $page;
        $model['pages'] = $pages;
    
        return 'gallery_view';
    }
}

function gallery_chosen_images(&$model) {
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        choose_images($_POST['check'], false);
        return 'redirect:'.$_SERVER['HTTP_REFERER'];
    }
    else {
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }
    
        $imagesPerPage = 4;
        $skip = ($page - 1) * $imagesPerPage;
    
        if(isset($_SESSION['check'])) {
            $images = get_chosen_images($_SESSION['check'], $skip, $imagesPerPage, $pages);
            $model['images'] = $images;
            $model['pages'] = $pages;
        }

        $model['page'] = $page;
    
        return 'gallery_chosen_images_view';
    }
}

function signup(&$model) {
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST["email"];
        $login = $_POST["uid"];
        $pwd = $_POST["pwd"];
        $pwdRepeat = $_POST["pwdrepeat"];
        $db = get_db();
        if(empty_input_signup($email, $login, $pwd, $pwdRepeat) !== false) {
            $error = 'emptyinput';
            $webpage = 'signup';
            return error_redirect($webpage, $error);
            exit();
        }
        if(invalid_uid($login) !== false) {
            $error = 'invaliduid';
            $webpage = 'signup';
            return error_redirect($webpage, $error);
            exit();
        }
        if(invalid_email($email) !== false) {
            $error = 'invalidemail';
            $webpage = 'signup';
            return error_redirect($webpage, $error);
            exit();
        }
        if(pwd_match($pwd, $pwdRepeat) !== false) {
            $error = 'passwordsdontmatch';
            $webpage = 'signup';
            return error_redirect($webpage, $error);
            exit();
        }
        if(uid_exists($db, $login) !== false) {
            $error = 'logintaken';
            $webpage = 'signup';
            return error_redirect($webpage, $error);
            exit();
        }
        if(email_exists($db, $email) !== false) {
            $error = 'emailtaken';
            $webpage = 'signup';
            return error_redirect($webpage, $error);
            exit();
        }
        create_user($db, $email, $login, $pwd, $model);
            $error = 'success';
            $webpage = 'signup';
            return error_redirect($webpage, $error);
    }
    else {
        if(isset($_GET["error"])) {
            switch($_GET["error"]) {
                case "emptyinput":
                    $model['errorText'] = "Fill in all the fields!";
                    break;
                case "invaliduid":
                    $model['errorText'] = "Choose a proper login!";
                    break;
                case "invalidemail":
                    $model['errorText'] = "Choose a proper e-mail!";
                    break;
                case "passwordsdontmatch":
                    $model['errorText'] = "Passwords don't match!";
                    break;
                case "logintaken":
                    $model['errorText'] = "The login is already taken!";
                    break;
                case "emailtaken":
                    $model['errorText'] = "The e-mail is already taken!";
                    break;
                case "success":
                    $model['errorText'] = "You have signed up!";
                    break;
            }
        }
        return 'signup_view';
    }
}

function login(&$model) {
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = $_POST["uid"];
        $pwd = $_POST["pwd"];
        $db = get_db();
        if(empty_input_login($login, $pwd) !== false) {
            $error = 'emptyinput';
            $webpage = 'login';
            return error_redirect($webpage, $error);
            exit();
        }
        if(uid_exists($db, $login) !== true) {
            $error = 'invaliduid';
            $webpage = 'login';
            return error_redirect($webpage, $error);
            exit();
        }
        $users = $db->users->find();
        foreach($users as $user) {
            if($user['login'] === $login)  {
                $pwdHashed = $user['password'];
                $login = $user['login'];
                break;
            }
        }
        $pwdCheck = password_verify($pwd, $pwdHashed);
        if($pwdCheck !== true) {
            $error = 'invalidpassword';
            $webpage = 'login';
            return error_redirect($webpage, $error);
            exit();
        }
        $_SESSION['login'] = $login;
        return 'index_view';
    }
    else {
        if(isset($_GET["error"])) {
            switch($_GET["error"]) {
                case "emptyinput":
                    $model['errorText'] = "Fill in all the fields!";
                    break;
                case "invaliduid":
                    $model['errorText'] = "Wrong login!";
                    break;
                case "invalidpassword":
                    $model['errorText'] = "Wrong password!";
                    break;
            }
        }
        return 'login_view';
    }
}

function logout(&$model) {
    unset($_SESSION['login']);
    return 'redirect:'.$_SERVER['HTTP_REFERER'];
}