# Advanced Medoo - Elevate Your Database Game with Medoo Extension

Welcome to Advanced Medoo, a powerful extension for [Medoo](https://medoo.in/) that takes your PHP database interactions to the next level. Say goodbye to repetitive database code and hello to a more efficient way of working with databases!

## Features
* **Custom Column Selection:** Advanced Medoo allows you to select columns in a more versatile way, making it easier to fetch the data you need.
* **Patch and Sync Functions:** Simplify data synchronization with patching and syncing functions, designed to streamline your workflow.
* **Column Similarity Search:** Easily determine how similar a specific column is to a given string with the "SIMILAR" function.

## Installation
You can quickly install Advanced Medoo using Composer. This will also automatically install Medoo.
```
composer require dandylion/advancedmedoo
```
## Usage
Getting started with Advanced Medoo is a breeze. Here's an example of how to use it:

```
use Dandylion\AdvancedMedoo;

// Initialize Advanced Medoo with your database configuration
$database = new AdvancedMedoo([
    'database_type' => 'mysql',
    'database_name' => 'your_database',
    'server' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password'
]);
```

### Select all columns from table
```
$database->select(
    'user',
    '[>]post'=>'user_id'
    [
        'user.name'
        'post.*'
    ]
);
//[{
//    "name": "my_name",
//    "user_id": 1,
//    "text": "post_text"
//    "date": "01/01/1970"
//}]

/* You can also put text in brackets to prepend a string in front of all the columns incase of duplicate column names */

$database->select(
    'user',
    '[>]post'=>'user_id'
    [
        'user.name'
        'post.* (post_)'
    ]
);

//[{
//    "name": "my_name",
//    "post_user_id": 1,
//    "post_text": "post_text",
//    "post_date": "01/01/1970"
//}]
```

## Patch method *(allows you to update or create a record without having to check if the record exists)*
```
//[
//    {
//        "user_id": 1,
//        "post_id": 1,
//        "text": "This is my first post",
//        "privacy": "public"
//    },
//    {
//        "user_id": 2,
//        "post_id": 2,
//        "text": "This is a different user post",
//        "privacy": "public"
//    }
//]
$database->patch(
    'posts',
    [
        [
            "user_id": 1,
            "post_id": 1,
            "text": "This is my first post",
            "privacy": "private"
        ],
        [
            "user_id": 1,
            "post_id": 3,
            "text": "This is my second post",
            "privacy": "public"
        ]
    ],
    [ // uses AND to check if record exists
        "user_id",
        "post_id"
    ]
);
//[
//    {
//        "user_id": 1,
//        "post_id": 1,
//        "text": "This is my first post",
//        "privacy": "private"
//    },
//    {
//        "user_id": 2,
//        "post_id": 2,
//        "text": "This is a different user post",
//        "privacy": "public"
//    },
//    {
//        "user_id": 1,
//        "post_id": 3,
//        "text": "This is my second post",
//        "privacy": "public"
//    }
//]
```

## Sync method *(same as patch method except will also delete records not found)*
```
//[
//    {
//        "user_id": 1,
//        "post_id": 1,
//        "text": "This is my first post",
//        "privacy": "public"
//    },
//    {
//        "user_id": 2,
//        "post_id": 2,
//        "text": "This is a different user post",
//        "privacy": "public"
//    }
//]
$database->patch(
    'posts',
    [
        [
            "user_id": 1,
            "post_id": 1,
            "text": "This is my first post",
            "privacy": "private"
        ],
        [
            "user_id": 1,
            "post_id": 3,
            "text": "This is my second post",
            "privacy": "public"
        ]
    ],
    [ // uses AND to check if record exists
        "user_id",
        "post_id"
    ]
);
//[
//    {
//        "user_id": 1,
//        "post_id": 1,
//        "text": "This is my first post",
//        "privacy": "private"
//    },
//    {
//        "user_id": 1,
//        "post_id": 3,
//        "text": "This is my second post",
//        "privacy": "public"
//    }
//]
```

## SIMILAR stored procedure
This stored procedure calculates how similar a column is to a given string.
> ***Please run this sql query on the database before using the sql function.***
```
DROP FUNCTION IF EXISTS SIMILAR;
DELIMITER //
CREATE FUNCTION SIMILAR(s1 VARCHAR(255), s2 VARCHAR(255))
RETURNS FLOAT
DETERMINISTIC
BEGIN
    DECLARE s1_len, s2_len, i, j, matches INT;
    DECLARE s3,checker VARCHAR(255);
    IF LENGTH(s1) > LENGTH(s2) THEN
    SET s3 = s2;
    SET s2 = s1;
    SET s1 = s3;
    END IF;
    SET s1_len = LENGTH(s1) - 2;
    SET s2_len = LENGTH(s2) - 2;
    SET i = 1;
    SET matches = 0;
    WHILE i <= s1_len DO
        SET j = 1;
        SET checker = SUBSTRING(s1,i,3);
        WHILE j <= s2_len DO
            IF checker = SUBSTRING(s2,j,3) THEN
                SET matches = matches + 1;
            END IF;
        SET j = j + 1;
        END WHILE;
        SET i = i + 1;
    END WHILE;
    RETURN matches/s1_len;
END//
DELIMITER ;
```

The SIMILAR function can be used in the column section of the select and get methods.
```

```

## License
Advanced Medoo is licensed under the MIT License.

## Acknowledgments
We extend our gratitude to the Medoo community for their exceptional database library.

##

Elevate your coding game with Advanced Medoo!