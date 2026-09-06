CREATE TABLE IF NOT EXISTS Users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  password VARCHAR(256) NOT NULL
);

CREATE TABLE IF NOT EXISTS Campaign (
  campaign_id INT PRIMARY KEY AUTO_INCREMENT,
  -- public_url VARCHAR(255) UNIQUE NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  image_url VARCHAR(255),
  date_cloture DATETIME,
  organiser_id INT,
  FOREIGN KEY (organiser_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Category (
  category_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(30) NOT NULL,
  campaign_id INT,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Candidate (
  candidate_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL, 
  age INT CHECK (age >= 0),
  theme VARCHAR(50) NOT NULL,
  description VARCHAR(255),
  bio TEXT,
  category_id INT,
  FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE CASCADE
);