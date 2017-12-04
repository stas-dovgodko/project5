DROP TABLE IF EXISTS student;

DROP TABLE IF EXISTS class;


CREATE TABLE `class` (
     degree INT(2) NOT NULL,
     letter VARCHAR(1) NOT NULL,
     title VARCHAR(255),

     PRIMARY KEY (degree, letter)
) ENGINE=INNODB;

CREATE TABLE `student` (
     id INT(11) NOT NULL AUTO_INCREMENT,
     fio VARCHAR(255),
     gender INT(1) DEFAULT NULL,

     degree INT(2) NOT NULL,
     letter VARCHAR(1) NOT NULL,

     PRIMARY KEY (id),
     INDEX (degree, letter),
     FOREIGN KEY (degree, letter) REFERENCES class(degree, letter) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=INNODB;
