<?php

use MongoDB\BSON\ObjectID;

function get_db() {
    $mongo = new MongoDB\Client(
        "mongodb://localhost:27017/wai",
        [
            'username' => 'wai_web',
            'password' => 'w@i_w3b',
        ]);
    $db = $mongo->wai;
    return $db;
}

function empty_input_signup($email, $login, $pwd, $pwdRepeat) {
    $result;
    if(empty($email) || empty($login) || empty($pwd) || empty($pwdRepeat)) {
        $result = true;
    }
    else {
        $result = false;
    }
    return $result;
}

function invalid_uid($login) {
    $result;
    if(!preg_match("/^[a-zA-Z0-9]*$/", $login)) {
        $result = true;
    }
    else {
        $result = false;
    }
    return $result;
}

function invalid_email($email) {
    $result;
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result = true;
    }
    else {
        $result = false;
    }
    return $result;
}

function pwd_match($pwd, $pwdRepeat) {
    $result;
    if($pwd !== $pwdRepeat) {
        $result = true;
    }
    else {
        $result = false;
    }
    return $result;
}

function uid_exists($db, $login) {
    $result = false;
    $users = $db->users->find();
    foreach($users as $user) {
        if($user['login'] === $login) {
            $result = true;
            break;
        }
    }
    return $result;
}

function email_exists($db, $email) {
    $result = false;
    $users = $db->users->find();
    foreach($users as $user) {
        if($user['email'] === $email)  {
            $result = true;
            break;
        }
    }
    return $result;
}

function create_user($db, $email, $login, $pwd, &$model) {
    $pwdHashed = password_hash($pwd, PASSWORD_DEFAULT);
    $user = [
        'email' => $email,
        'login' => $login,
        'password' => $pwdHashed
    ];
    $db->users->insertOne($user);
}

function error_redirect($webpage, $error) {
    if($webpage === 'gallery') {
        if(isset($_GET["page"])) {
            if(isset($_GET["error"])) {
                if($_GET["error"] === $error) {
                    return 'redirect:'.$_SERVER['HTTP_REFERER'];
                }
                else {
                    return 'redirect:'.$webpage.'?page='.$_GET["page"].'&error='.$error;
                }
            }
            else {
                return 'redirect:'.$webpage.'?page='.$_GET["page"].'&error='.$error;
            }
        }
        else {
            return 'redirect:'.$webpage.'?page=1&error='.$error;
        }
    }
    else {
        if(isset($_GET["error"])) {
            if($_GET["error"] === $error) {
                return 'redirect:'.$_SERVER['HTTP_REFERER'];
            }
            else {
                return 'redirect:'.$webpage.'?error='.$error;
            }
        }
        else {
            return 'redirect:'.$webpage.'?error='.$error;
        }
    }
}

function empty_input_login($login, $pwd) {
    $result;
    if(empty($login) || empty($pwd)) {
        $result = true;
    }
    else {
        $result = false;
    }
    return $result;
}

function get_images($skip, $imagesPerPage, &$pages) {
    if(isset($_SESSION['login'])) {
        $query = ['$or' => [
            ['author' => $_SESSION['login']],
            ['view' => 'public']
        ]];
    }
    else {
        $query = ['view' => 'public'];
    }
    

    $options = [
        'skip' => $skip,
        'limit' => $imagesPerPage
    ];

    $db = get_db();

    $count = $db->images->count();

    if($count % $imagesPerPage === 0) {
        $pages = intdiv($count, $imagesPerPage);
    }
    else {
        $pages = intdiv($count, $imagesPerPage) + 1;
    }

    return $images = $db->images->find($query, $options)->toArray();
}

function upload_image($imageTitle, $imageAuthor, $imageWatermark, $imageView, $image, &$model, &$error) {
    $imageName = $image["name"];
    $imageType = $image["type"];
    $imageTempName = $image["tmp_name"];
    $imageError = $image["error"];
    $imageSize = $image["size"];
    $imageTempExtension = explode(".", $imageName);
    $imageExtension = strtolower(end($imageTempExtension));
    $allowedExtensions = array("jpg", "jpeg", "png");

    if(!empty($imageTitle) && !empty($imageAuthor) && !empty($imageWatermark)) {
        if($imageError === 0) {
            if(in_array($imageExtension, $allowedExtensions)) {
                if($imageSize <= 1000000) {
                    $imageNewName = uniqid("", true).".".$imageName;
                    $imageDestination = "../web/images/original/".$imageNewName;
                    $db = get_db();
                    $image = [
                        'title' => $imageTitle,
                        'author' => $imageAuthor,
                        'filename' => $imageNewName,
                        'watermark' => $imageWatermark,
                        'view' => $imageView
                    ];
                    $db->images->insertOne($image);

                    require 'business_image.php';

                    move_uploaded_file($imageTempName,$imageDestination);
                    create_thumbnail($imageExtension, $imageDestination, $imageNewName);
                    create_watermarked($imageExtension, $imageDestination, $imageWatermark, $imageNewName);

                    $model['errorText'] = "The image has been uploaded!";
                    $error = 'success';
                }
                else {
                    $model['errorText'] = "The image is too big!";
                    $error = 'toobigimg';
                }
            }
            else {
                $model['errorText'] = "Choose a proper picture!";
                $error = 'invalidimg';
            }
        }
        else {
            $model['errorText'] = "An error has occurred!";
            $error = 'error';
        }
    }
    else {
        $model['errorText'] = "Fill in all the fields!";
        $error = 'emptyinput';
    }
}

function choose_images($checks, $markValue) {
    if(!empty($checks)) {
        if($markValue === true) {
            if(isset($_SESSION['check[]'])) {
                foreach($checks as $check) {
                    if(!in_array($check,$_SESSION['check[]'])) {
                        array_push($_SESSION['check[]'],$check);
                    }
                }
            }
            else {
                $_SESSION['check'] = $checks;
            }
        }
        else {
            $_SESSION['check'] = array_diff($_SESSION['check'],$checks);
        }
    }
}

function get_chosen_images($checks, $skip, $imagesPerPage, &$pages) {
    $options = [
        'skip' => $skip,
        'limit' => $imagesPerPage
    ];

    $db = get_db();
    for($i = 0; $i < count($checks); $i++) {
        $checks[$i] = new ObjectId($checks[$i]);
    }

    $query = ['_id' => ['$in' => $checks]];
    $count = $db->images->count($query);

    if($count % $imagesPerPage === 0) {
        $pages = intdiv($count, $imagesPerPage);
    }
    else {
        $pages = intdiv($count, $imagesPerPage) + 1;
    }

    $images = $db->images->find($query, $options)->toArray();

    return $images;
}