<?php

session_start();

require_once __DIR__ . "/../database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter email and password.";

    } else {

        $sql = "SELECT id, email, password, role
                FROM admins
                WHERE email = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

    

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $admin = $result->fetch_assoc();

                if (password_verify($password, $admin["password"])) {

                    if ($admin["role"] === "admin") {

                        $_SESSION["admin_id"] = $admin["id"];
                        $_SESSION["admin_email"] = $admin["email"];
                        $_SESSION["admin_role"] = $admin["role"];


                        // Correct because login.php is inside admin/
                        header("Location: dashboard.php");
                        exit();

                    } else {

                        $error = "You are not authorized as an admin.";

                    }

                } else {

                    $error = "Invalid email or password.";

                }

            } else {

                $error = "Invalid email or password.";

            }

            $stmt->close();
        }
    }


?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Login - Expense Finance</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #2856c7;

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {

            width: 430px;

            background: white;

            padding: 36px 40px;

            border-radius: 16px;

            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .logo {

            width: 55px;
            height: 55px;

            background: #2864e8;

            border-radius: 13px;

            margin: 0 auto 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-size: 25px;
        }

        .title {

            text-align: center;

            color: #102c67;

            font-size: 27px;

            margin-bottom: 6px;
        }

        .subtitle {

            text-align: center;

            color: #718096;

            margin-bottom: 30px;
        }

        .login-heading {

            color: #102c67;

            font-size: 23px;

            margin-bottom: 22px;
        }

        .error {

            background: #fff1f1;

            color: #d32f2f;

            padding: 10px 12px;

            border-radius: 6px;

            margin-bottom: 15px;

            font-size: 14px;
        }

        .form-group {

            margin-bottom: 20px;
        }

        label {

            display: block;

            color: #263b63;

            font-weight: bold;

            font-size: 14px;

            margin-bottom: 8px;
        }

        input {

            width: 100%;

            padding: 13px;

            border: 1px solid #cbd5e1;

            border-radius: 7px;

            font-size: 14px;

            outline: none;
        }

        input:focus {

            border-color: #2864e8;

            box-shadow: 0 0 0 2px rgba(40,100,232,0.1);
        }

        .login-btn {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 7px;

            background: #2864e8;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;
        }

        .login-btn:hover {

            background: #1e4fc1;
        }

        .back-link {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #5b708f;

            text-decoration: none;

            font-size: 14px;
        }

        .back-link:hover {

            color: #2864e8;
        }

    </style>

</head>

<body>

    <div class="login-container">

        <div class="logo">
            👤
        </div>

        <h1 class="title">
            Expense Finance
        </h1>

        <p class="subtitle">
            Administrator Portal
        </p>


        <h2 class="login-heading">
            Admin Login
        </h2>


        <?php if ($error !== ""): ?>

            <div class="error">
                ⚠ <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="">

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                    required>

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required>

            </div>


            <button
                type="submit"
                class="login-btn">

                Admin Login

            </button>

        </form>


        <a href="login.php" class="back-link">
            ← Back to login selection
        </a>

    </div>

</body>

</html>