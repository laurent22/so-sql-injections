CREATE TABLE `questions` (
	`id` int(11) NOT NULL,
	`body_markdown` TEXT,
	`raw_json` TEXT,
PRIMARY KEY (`id`)
) CHARACTER SET=utf8;

ALTER TABLE questions ADD creation_date int(11) default "0";
ALTER TABLE questions ADD has_sql_injection tinyint(1) default "0";
ALTER TABLE questions ADD sql_injection_line int(11) default "0";
ALTER TABLE questions ADD is_processed tinyint(1) default "0";
ALTER TABLE questions ADD has_sql tinyint(1) default "0";
ALTER TABLE questions CHANGE id question_id int(11) NOT NULL;
-- For Eloquent:
ALTER TABLE questions ADD updated_at datetime;
ALTER TABLE questions ADD created_at datetime;
UPDATE questions SET created_at = '2016-09-29 00:00:00', updated_at = '2016-09-29 00:00:00';

ALTER TABLE questions ADD owner_id int(11) default "0";

CREATE TABLE `users` (
	`user_id` int(11) NOT NULL,
	`age` int(11) NOT NULL default "0",
	`reputation` int(11) NOT NULL default "0",
	`is_employee` tinyint(1) NOT NULL default "0",
	`location` varchar(255) NOT NULL default "",
	`raw_json` TEXT NOT NULL DEFAULT "",
PRIMARY KEY (`user_id`)
) CHARACTER SET=utf8;

ALTER TABLE users ADD updated_at datetime;
ALTER TABLE users ADD created_at datetime;
UPDATE users SET created_at = '2016-09-30 00:00:00', updated_at = '2016-09-30 00:00:00';

ALTER TABLE `questions` ADD INDEX `is_processed` (`is_processed`);
ALTER TABLE `questions` ADD INDEX `creation_date` (`creation_date`);
ALTER TABLE `questions` ADD INDEX `question_id` (`question_id`);
ALTER TABLE `questions` ADD INDEX `has_sql` (`has_sql`);
ALTER TABLE `questions` ADD INDEX `has_sql_injection` (`has_sql_injection`);
