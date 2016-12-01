# Questions

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
ALTER TABLE questions ADD updated_at datetime; -- For Eloquent:
ALTER TABLE questions ADD created_at datetime; -- For Eloquent:
UPDATE questions SET created_at = '2016-09-29 00:00:00', updated_at = '2016-09-29 00:00:00';
ALTER TABLE questions ADD owner_id int(11) default "0";
ALTER TABLE `questions` ADD INDEX `is_processed` (`is_processed`);
ALTER TABLE `questions` ADD INDEX `creation_date` (`creation_date`);
ALTER TABLE `questions` ADD INDEX `question_id` (`question_id`);
ALTER TABLE `questions` ADD INDEX `has_sql` (`has_sql`);
ALTER TABLE `questions` ADD INDEX `has_sql_injection` (`has_sql_injection`);
ALTER TABLE `questions` ADD INDEX `owner_id` (`owner_id`);

# Users

CREATE TABLE `users` (
	`user_id` int(11) NOT NULL,
	`age` int(11) NOT NULL default "0",
	`reputation` int(11) NOT NULL default "0",
	`is_employee` tinyint(1) NOT NULL default "0",
	`location` varchar(255) NOT NULL default "",
	`raw_json` TEXT NOT NULL DEFAULT "",
PRIMARY KEY (`user_id`)
) CHARACTER SET=utf8;
ALTER TABLE users ADD country varchar(2);
ALTER TABLE users ADD updated_at datetime;
ALTER TABLE users ADD created_at datetime;
ALTER TABLE users CHANGE location location varchar(255) NULL;
UPDATE users SET created_at = '2016-09-30 00:00:00', updated_at = '2016-09-30 00:00:00';
ALTER TABLE `users` ADD INDEX `user_id` (`user_id`);
ALTER TABLE `users` ADD INDEX `country` (`country`);

# Countries

CREATE TABLE `countries` (
	`geoname_id` int(11) NOT NULL,
	`code` varchar(2) NOT NULL,
	PRIMARY KEY (`geoname_id`)
) CHARACTER SET=utf8;
ALTER TABLE `countries` ADD INDEX `geoname_id` (`geoname_id`);
ALTER TABLE `countries` ADD INDEX `code` (`code`);

# Cities

CREATE TABLE `cities` (
	`geoname_id` int(11) NOT NULL,
	`country_id` int(11) NOT NULL,
	PRIMARY KEY (`geoname_id`)
) CHARACTER SET=utf8;
ALTER TABLE `cities` ADD INDEX `geoname_id` (`geoname_id`);
ALTER TABLE `cities` ADD INDEX `country_id` (`country_id`);

# Places

CREATE TABLE `places` (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`city_id` int(11) NULL DEFAULT NULL,
	`country_id` int(11) NULL DEFAULT NULL,
	`name` varchar(64) NOT NULL
) CHARACTER SET=utf8;
ALTER TABLE `places` ADD INDEX `city_id` (`city_id`);
ALTER TABLE `places` ADD INDEX `country_id` (`country_id`);
ALTER TABLE `places` ADD INDEX `name` (`name`);
