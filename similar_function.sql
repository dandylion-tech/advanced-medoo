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