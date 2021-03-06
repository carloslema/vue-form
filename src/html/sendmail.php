<?php
  mb_internal_encoding("UTF-8");

  // Common attributes
  $boundary = "__BOUNDARY__";

  // Require functions
  require "./functions.php";

  // ------------------------------------
  // Start
  // ------------------------------------
  // Only process POST reqeusts.
  if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Not a POST request, set a 403 (forbidden) response code.
    http_response_code(403);
    echo "403 forbidden";
    exit;
  }

  // Get the form fields and remove whitespace.
  $name = removeCRLF(removeHtml($_POST["name"]));
  $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
  $radio = removeHtml($_POST["radio"]);
  $checkbox = removeHtml(implode(" / ", $_POST["checkbox"]));
  $select = removeHtml(implode(" / ", $_POST["select"]));
  $multi_text = removeHtml($_POST["multiText"]);

  // Check that data was sent to the mailer.
  if (
    empty($name) OR
    empty($multi_text) OR
    !filter_var($email, FILTER_VALIDATE_EMAIL)
  ) {
    // Set a 400 (bad request) response code and exit.
    http_response_code(400);
    echo "400 bad request";
    exit;
  }

  // ------------------------------------
  // Upload attachment files
  // ------------------------------------
  $file = $_FILES["file"];
  $filepath = null;

  if ($file['name']) {
    // Check file type.
    switch ($file['type']) {
      case 'image/jpeg':
      case 'image/png':
      case 'image/gif':
        break;
      default:
        http_response_code(400);
        echo "400 bad request";
        exit;
    }

    // Check to be present directory.
    $dirArray = Array('./upload_files/', date('Y/'), date('md/'), date('His/'));
    for ($i = 0; $i < count($dirArray); $i++) {
      $dir = '';
      for ($j = 0; $j <= $i; $j++) {
        $dir .= $dirArray[$j];
      }
      if (!is_dir($dir)) {
        mkdir($dir);
      }
    }

    // Upload file.
    $filepath = implode($dirArray). $file['name'];
    move_uploaded_file($file['tmp_name'], $filepath);
  }

  // ------------------------------------
  // Start setting email headers and contents.
  // ------------------------------------
  // Set the email headers.
  $email_headers = "";
  if ($filepath) {
    $email_headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"; charset=\"UTF-8\"\n";
    $email_headers .= "Content-Transfer-Encoding: base64\n";
  } else {
    $email_headers .= "Content-Type: text/plain; charset=\"UTF-8\"\n";
    $email_headers .= "Content-Transfer-Encoding: 8bit\n";
  }

  // Build the email content.
  $email_content = "";
  $email_content .= "Name: $name\n";
  $email_content .= "Email: $email\n";
  $email_content .= "Radios: $radio\n";
  $email_content .= "Checks: $checkbox\n";
  $email_content .= "Selected: $select\n";
  $email_content .= "Multi Text: $multi_text\n";

  // ------------------------------------
  // For Admin
  // ------------------------------------
  // Set the recipient email address.
  // FIXME: Update this to your desired email address.
  $recipient_admin = "info@tplh.net";

  // Set the email subject.
  $subject_admin = mb_encode_mimeheader("$name 様よりお問い合わせがありました。", "UTF-8");

  // Build the email content.
  $email_content_admin = "ウェブサイトのフォームからお問い合わせがありました。\n内容は以下のとおりです。\n\n";
  $email_content_admin .= $email_content;
  $email_content_admin = getMailContent($email_content_admin, $boundary, $filepath);

  // Build the email headers.
  $email_headers_admin = '';
  $email_headers_admin .= $email_headers;
  $email_headers_admin .= "From: ". mb_encode_mimeheader($name, "UTF-8"). " <$email>";

  // ------------------------------------
  // For User
  // ------------------------------------
  // Set the recipient email address.
  // FIXME: Update this to your desired email address.
  $recipient_user = $email;

  // Set the email subject.
  $subject_user = mb_encode_mimeheader("$name 様 お問い合わせありがとうございました。", "UTF-8");

  // Build the email content.
  $email_content_user  = "$name 様\n\n";
  $email_content_user .= "この度はお問い合わせいただきましてありがとうございました。\n以下の内容にて承りました。\n\n";
  $email_content_user .= "$email_content\n";
  $email_content_user = getMailContent($email_content_user, $boundary, $filepath);

  // Build the email headers.
  $email_headers_user = '';
  $email_headers_user .= $email_headers;
  $email_headers_user .= "From: ". mb_encode_mimeheader("yoichi kobayashi", "UTF-8"). " <info@tplh.net>";

  // ------------------------------------
  // Send Mail
  // ------------------------------------
  if (
    mail($recipient_admin, $subject_admin, $email_content_admin, $email_headers_admin) &&
    mail($recipient_user, $subject_user, $email_content_user, $email_headers_user)
  ) {
    // Set a 200 (okay) response code.
    http_response_code(200);
    echo "200 OK";
  } else {
    // Set a 500 (internal server error) response code.
    http_response_code(500);
    echo "500 internal server error";
  }
?>
