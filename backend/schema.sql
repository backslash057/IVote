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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organiser_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Category (
  category_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(30) NOT NULL,
  campaign_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Candidate (
  candidate_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL, 
  candidate_number INT NOT NULL CHECK (candidate_number > 0),
  age INT CHECK (age >= 0),
  theme VARCHAR(50) NOT NULL,
  description VARCHAR(255),
  bio TEXT,
  category_id INT,
  campaign_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE CASCADE,
  FOREIGN KEY (campaign_id) REFERENCES Campaign(campaign_id) ON DELETE CASCADE,
  UNIQUE KEY uq_candidate_number_per_campaign (campaign_id, candidate_number)
);

CREATE TABLE IF NOT EXISTS Vote (
  vote_id INT PRIMARY KEY AUTO_INCREMENT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  candidate_id INT,
  payment_method ENUM("MOMO", "OM"),
  vote_count INT,
  FOREIGN KEY (candidate_id) REFERENCES Candidate(candidate_id) ON DELETE CASCADE
);

-- Initial demo data. Temporary passwords:
-- user@gmail.com -> TempUser@123
-- orga@gmail.com -> TempOrga@123
INSERT INTO Users (email, name, password)
SELECT 'user@gmail.com', 'IVote User', '$2y$12$Oi1IdtJNPEDuoUSAbitMDuvWWQd67pAcI0Qm/0hCKuvmJn7wXw.Iq'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'user@gmail.com');

INSERT INTO Users (email, name, password)
SELECT 'orga@gmail.com', 'IVote Organizer', '$2y$12$/4hF1uNr4OZJu57Y0HxMb.AxUzbAHwbTRuG8yoRZkdt7bF8FQI50u'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'orga@gmail.com');

INSERT INTO Campaign (title, description, image_url, date_cloture, organiser_id)
SELECT 'Élection Miss & Master UY1 2026',
       'Grande Finale Annuelle de l''Excellence, du Charisme et du Leadership Académique',
       'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1400&auto=format&fit=crop&q=80',
       '2026-09-02 23:59:59',
       (SELECT user_id FROM Users WHERE email = 'orga@gmail.com')
WHERE NOT EXISTS (SELECT 1 FROM Campaign WHERE title = 'Élection Miss & Master UY1 2026');

INSERT INTO Campaign (title, description, image_url, date_cloture, organiser_id)
SELECT 'AfroTech Pitch Awards 2026',
       'Prix du Public pour les Meilleurs Startuppers et Innovateurs Africains',
       'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400&auto=format&fit=crop&q=80',
       '2026-09-10 23:59:59',
       (SELECT user_id FROM Users WHERE email = 'orga@gmail.com')
WHERE NOT EXISTS (SELECT 1 FROM Campaign WHERE title = 'AfroTech Pitch Awards 2026');

INSERT INTO Campaign (title, description, image_url, date_cloture, organiser_id)
SELECT 'Indie Sound Africa Awards',
       'Vote officiel du Meilleur Artiste Révélation Vocale & Musique Urbaine',
       'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1400&auto=format&fit=crop&q=80',
       '2026-08-30 23:59:59',
       (SELECT user_id FROM Users WHERE email = 'orga@gmail.com')
WHERE NOT EXISTS (SELECT 1 FROM Campaign WHERE title = 'Indie Sound Africa Awards');

INSERT INTO Category (name, campaign_id)
SELECT seed.name, campaign.campaign_id
FROM (
  SELECT 'Miss' AS name, 'Élection Miss & Master UY1 2026' AS title
  UNION ALL SELECT 'Master', 'Élection Miss & Master UY1 2026'
  UNION ALL SELECT 'Prix Spécial du Public', 'Élection Miss & Master UY1 2026'
  UNION ALL SELECT 'Tech', 'AfroTech Pitch Awards 2026'
  UNION ALL SELECT 'Agritech', 'AfroTech Pitch Awards 2026'
  UNION ALL SELECT 'Green Energy', 'AfroTech Pitch Awards 2026'
  UNION ALL SELECT 'Révélation Féminine', 'Indie Sound Africa Awards'
  UNION ALL SELECT 'Révélation Masculine', 'Indie Sound Africa Awards'
  UNION ALL SELECT 'Meilleur Clip', 'Indie Sound Africa Awards'
) AS seed
JOIN Campaign campaign ON campaign.title = seed.title
WHERE NOT EXISTS (
  SELECT 1 FROM Category existing
  WHERE existing.name = seed.name AND existing.campaign_id = campaign.campaign_id
);

INSERT INTO Candidate (name, candidate_number, age, theme, description, bio, category_id, campaign_id)
SELECT seed.name, seed.candidate_number, seed.age, seed.theme, seed.description, seed.bio, category.category_id, campaign.campaign_id
FROM (
  SELECT 'Amina Diallo' AS name, 1 AS candidate_number, 21 AS age, 'Miss' AS theme,
         'Développeuse et fondatrice de Girls in Code Cameroon.' AS description,
         'Passionnée de technologie et engagée pour l''autonomisation de la jeunesse.' AS bio,
         'Miss' AS category_name, 'Élection Miss & Master UY1 2026' AS campaign_title
  UNION ALL SELECT 'Koffi Mensah', 2, 23, 'Master', 'Futur chirurgien et capitaine de débat universitaire.', 'Pianiste classique et défenseur de l''excellence au service de tous.', 'Master', 'Élection Miss & Master UY1 2026'
  UNION ALL SELECT 'Grace Nkem', 3, 22, 'Miss', 'Oratrice primée engagée dans l''accès aux soins.', 'Magistrate étudiante, elle défend intégrité, justice et bienveillance.', 'Miss', 'Élection Miss & Master UY1 2026'
  UNION ALL SELECT 'Yannick Tcham', 4, 22, 'Master', 'Jeune entrepreneur en agritech verte.', 'Ambassadeur du développement durable sur le campus.', 'Master', 'Élection Miss & Master UY1 2026'
  UNION ALL SELECT 'Sara Benali', 5, 21, 'Prix Spécial du Public', 'Designer d''espaces écologiques et artiste peintre.', 'Elle imagine des espaces modernes et durables pour les générations futures.', 'Prix Spécial du Public', 'Élection Miss & Master UY1 2026'
  UNION ALL SELECT 'David Osei', 6, 24, 'Prix Spécial du Public', 'Chercheur en robotique de drones.', 'Mentor pour les lycéens dans les filières STEM.', 'Prix Spécial du Public', 'Élection Miss & Master UY1 2026'
  UNION ALL SELECT 'Nadia Eteme', 1, 24, 'Tec h', 'Plateforme de santé numérique accessible.', 'Entrepreneure spécialisée dans les solutions numériques inclusives.', 'Tech', 'AfroTech Pitch Awards 2026'
  UNION ALL SELECT 'Moussa Traore', 2, 27, 'Agritech', 'Outils intelligents pour les petits producteurs.', 'Ingénieur agronome qui modernise les chaînes agricoles locales.', 'Agritech', 'AfroTech Pitch Awards 2026'
  UNION ALL SELECT 'Chisom Okafor', 3, 25, 'Green Energy', 'Mini-réseaux solaires pour les communautés rurales.', 'Fondatrice d''une startup dédiée à l''énergie propre en Afrique.', 'Green Energy', 'AfroTech Pitch Awards 2026'
  UNION ALL SELECT 'Aya Mbaye', 1, 22, 'Révélation Féminine', 'Voix soul et afro-pop montante.', 'Autrice-compositrice qui mêle traditions et productions modernes.', 'Révélation Féminine', 'Indie Sound Africa Awards'
  UNION ALL SELECT 'Jean Kondo', 2, 26, 'Révélation Masculine', 'Artiste afro-fusion et producteur indépendant.', 'Il construit un univers musical entre hip-hop, makossa et afrobeats.', 'Révélation Masculine', 'Indie Sound Africa Awards'
  UNION ALL SELECT 'Lina Sarr', 3, 23, 'Meilleur Clip', 'Réalisatrice et directrice artistique.', 'Elle crée des clips qui donnent une identité visuelle forte aux artistes africains.', 'Meilleur Clip', 'Indie Sound Africa Awards'
) AS seed
JOIN Campaign campaign ON campaign.title = seed.campaign_title
JOIN Category category ON category.name = seed.category_name AND category.campaign_id = campaign.campaign_id
WHERE NOT EXISTS (
  SELECT 1 FROM Candidate existing
  WHERE existing.campaign_id = campaign.campaign_id
    AND existing.candidate_number = seed.candidate_number
);

INSERT INTO Vote (candidate_id, payment_method, vote_count)
SELECT candidate.candidate_id, seed.payment_method, seed.vote_count
FROM (
  SELECT 'Amina Diallo' AS name, 'MOMO' AS payment_method, 14820 AS vote_count
  UNION ALL SELECT 'Koffi Mensah', 'OM', 12450
  UNION ALL SELECT 'Grace Nkem', 'MOMO', 9340
  UNION ALL SELECT 'Yannick Tcham', 'OM', 6730
  UNION ALL SELECT 'Sara Benali', 'MOMO', 4980
  UNION ALL SELECT 'David Osei', 'OM', 4120
  UNION ALL SELECT 'Nadia Eteme', 'MOMO', 12000
  UNION ALL SELECT 'Moussa Traore', 'OM', 9500
  UNION ALL SELECT 'Chisom Okafor', 'MOMO', 6910
  UNION ALL SELECT 'Aya Mbaye', 'OM', 28000
  UNION ALL SELECT 'Jean Kondo', 'MOMO', 24000
  UNION ALL SELECT 'Lina Sarr', 'OM', 19200
) AS seed
JOIN Candidate candidate ON candidate.name = seed.name
WHERE NOT EXISTS (
  SELECT 1 FROM Vote existing
  WHERE existing.candidate_id = candidate.candidate_id
    AND existing.vote_count = seed.vote_count
);