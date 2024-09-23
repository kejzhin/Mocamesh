CREATE TABLE IF NOT EXISTS records (
    records_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    phoneNumber VARCHAR(50) NOT NULL,
    voucher VARCHAR(255) NOT NULL,
    reference VARCHAR(9) NOT NULL
);