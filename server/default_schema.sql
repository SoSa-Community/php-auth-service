DROP SCHEMA IF EXISTS `main`;
CREATE SCHEMA IF NOT EXISTS `main` DEFAULT CHARACTER SET utf8mb4 ;

USE `main`;

CREATE TABLE `user` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email_hash` VARCHAR(32) NULL,
  `created` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_unqiue` (`username` ASC)
);

CREATE TABLE `password_reset` (
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `token` VARCHAR(255) NULL,
  `expiry` DATETIME NULL,
  `transient` VARCHAR(100) NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

CREATE TABLE `main`.`provider_user` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(45) NULL,
  `unique_id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `access_token` VARCHAR(255) NULL,
  `refresh_token` VARCHAR(255) NULL,
  `access_token_expiry` DATETIME NULL,
  `created` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `provider_unique` (`provider` ASC, `unique_id` ASC),
  INDEX `provider_user_unique` (`provider` ASC, `unique_id` ASC, `user_id` ASC)
);
