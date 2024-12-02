CREATE TABLE marks (
    staffid VARCHAR(255) NOT NULL,
    module_name VARCHAR(255) NOT NULL,
    count INT NOT NULL,
    role_mark INT NOT NULL,
    level_mark INT NOT NULL,
    total_marks INT NOT NULL,
    PRIMARY KEY (staffid, module_name)
);
