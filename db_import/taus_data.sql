-- taus_data.sql
-- Replace or remove CREATE DATABASE if you prefer to create DB via phpMyAdmin UI

DROP DATABASE IF EXISTS `taus_data`;
CREATE DATABASE `taus_data` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `taus_data`;

-- tbl_student
CREATE TABLE `tbl_student` (
  `studentID` INT NOT NULL AUTO_INCREMENT,
  `firstName` VARCHAR(50) NOT NULL,
  `lastName` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  PRIMARY KEY (`studentID`)
) ENGINE=InnoDB;

-- tbl_class
CREATE TABLE `tbl_class` (
  `classID` INT NOT NULL AUTO_INCREMENT,
  `className` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100),
  PRIMARY KEY (`classID`)
) ENGINE=InnoDB;

-- tbl_student_class: junction table
CREATE TABLE `tbl_student_class` (
  `studentID` INT NOT NULL,
  `classID` INT NOT NULL,
  PRIMARY KEY (`studentID`,`classID`),
  CONSTRAINT `fk_sc_student` FOREIGN KEY (`studentID`) REFERENCES `tbl_student`(`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sc_class` FOREIGN KEY (`classID`) REFERENCES `tbl_class`(`classID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Insert sample data: at least 3 rows per table
INSERT INTO `tbl_student` (`firstName`,`lastName`,`email`) VALUES
('Alice','Garcia','alice.garcia@example.edu'),
('Ben','Harris','ben.harris@example.edu'),
('Cara','Nguyen','cara.nguyen@example.edu');

INSERT INTO `tbl_class` (`className`,`location`) VALUES
('Intro to Web Dev','Room 101'),
('Database Systems','Room 204'),
('Algorithms','Room 302');

-- Enrollments (map students to classes)
INSERT INTO `tbl_student_class` (`studentID`,`classID`) VALUES
(1,1),
(1,2),
(2,2),
(3,1),
(3,3);

-- Create view: selects all students and the classes they are enrolled in
CREATE OR REPLACE VIEW `vw_student_classes` AS
SELECT
  s.studentID,
  s.firstName,
  s.lastName,
  s.email,
  c.classID,
  c.className,
  c.location
FROM tbl_student s
LEFT JOIN tbl_student_class sc ON s.studentID = sc.studentID
LEFT JOIN tbl_class c ON sc.classID = c.classID;

-- Stored procedures: use delimiter so phpMyAdmin/MySQL treats multiple statements
DELIMITER $$

CREATE PROCEDURE `sp_getStudents`()
BEGIN
  SELECT * FROM tbl_student;
END $$
 
CREATE PROCEDURE `sp_getStudentByEmail`(IN p_email VARCHAR(100))
BEGIN
  SELECT * FROM tbl_student WHERE email = p_email;
END $$

CREATE PROCEDURE `sp_insertStudent`(
  IN p_firstName VARCHAR(50),
  IN p_lastName VARCHAR(50),
  IN p_email VARCHAR(100)
)
BEGIN
  INSERT INTO tbl_student (firstName, lastName, email)
  VALUES (p_firstName, p_lastName, p_email);
  -- Optionally return the new studentID
  SELECT LAST_INSERT_ID() AS new_studentID;
END $$

DELIMITER ;
