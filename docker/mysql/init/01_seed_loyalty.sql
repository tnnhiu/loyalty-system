CREATE TABLE IF NOT EXISTS loyalty_member (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    points INT NOT NULL DEFAULT 0
);

INSERT INTO loyalty_member (name, points)
VALUES ('Nguyen Van A', 120), ('Tran Thi B', 250), ('Le Van C', 90);
