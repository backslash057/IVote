-- Donnees de demonstration IVote (environnement local uniquement).
-- Script relancable : les campagnes, candidats, votes et retraits existants
-- ne sont pas modifies ni dupliques.

START TRANSACTION;

INSERT INTO Campaign (title, description, image_url, price_per_vote, is_draft, date_cloture, organiser_id)
SELECT seed.title, seed.description, seed.image_url, seed.price_per_vote, 0, seed.date_cloture, users.user_id
FROM (
    SELECT 'Festival des Talents de Yaounde 2026' AS title,
           'Le public choisit les artistes et danseurs qui feront la grande scene du festival.' AS description,
           'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=1400' AS image_url,
           100 AS price_per_vote, '2027-12-31 23:59:59' AS date_cloture,
           'orga@gmail.com' AS organizer_email
    UNION ALL
    SELECT 'Startup Impact Cameroon 2026',
           'Decouvrez les jeunes entreprises qui proposent des solutions utiles aux communautes.',
           'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1400',
           200, '2027-12-31 23:59:59', 'ngnintedemjunior@gmail.com'
    UNION ALL
    SELECT 'Voix du Campus 2026',
           'Concours musical etudiant : votez pour votre voix et votre chorale preferees.',
           'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=1400',
           150, '2027-12-31 23:59:59', 'avengers@gmail.com'
) AS seed
JOIN Users AS users ON users.email = seed.organizer_email
WHERE NOT EXISTS (
    SELECT 1 FROM Campaign AS existing WHERE existing.title = seed.title
);

INSERT INTO Category (name, campaign_id)
SELECT seed.name, campaigns.campaign_id
FROM (
    SELECT 'Festival des Talents de Yaounde 2026' AS campaign_title, 'Musique' AS name
    UNION ALL SELECT 'Festival des Talents de Yaounde 2026', 'Danse'
    UNION ALL SELECT 'Startup Impact Cameroon 2026', 'Technologie'
    UNION ALL SELECT 'Startup Impact Cameroon 2026', 'Agritech'
    UNION ALL SELECT 'Voix du Campus 2026', 'Voix solo'
    UNION ALL SELECT 'Voix du Campus 2026', 'Chorales'
) AS seed
JOIN Campaign AS campaigns ON campaigns.title = seed.campaign_title
WHERE NOT EXISTS (
    SELECT 1 FROM Category AS existing
    WHERE existing.campaign_id = campaigns.campaign_id AND existing.name = seed.name
);

INSERT INTO Candidate (name, candidate_number, age, theme, description, bio, image_url, category_id, campaign_id)
SELECT seed.name, seed.candidate_number, seed.age, seed.theme, seed.description, seed.bio,
       seed.image_url, categories.category_id, campaigns.campaign_id
FROM (
    SELECT 'Mireille Nono' AS name, 1 AS candidate_number, 22 AS age, 'Afro-pop' AS theme,
           'Chanteuse et autrice-compositrice de Yaounde.' AS description,
           'Elle melange rythmes modernes et sonorites camerounaises.' AS bio,
           'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500' AS image_url,
           'Musique' AS category_name, 'Festival des Talents de Yaounde 2026' AS campaign_title
    UNION ALL SELECT 'Les Etoiles de Melen', 2, 24, 'Makossa', 'Groupe de musique live fonde par des etudiants.', 'Le groupe revisite les classiques et compose ses propres titres.', 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=500', 'Musique', 'Festival des Talents de Yaounde 2026'
    UNION ALL SELECT 'Urban Move Crew', 3, 21, 'Danse urbaine', 'Collectif de danse et de creation choregraphique.', 'Une troupe de jeunes danseurs passionnes par la scene.', 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?w=500', 'Danse', 'Festival des Talents de Yaounde 2026'
    UNION ALL SELECT 'SanaCare', 1, 25, 'Sante numerique', 'Outil de suivi medical accessible depuis un telephone.', 'Une equipe qui simplifie le suivi des soins de proximite.', 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=500', 'Technologie', 'Startup Impact Cameroon 2026'
    UNION ALL SELECT 'AgriLink', 2, 27, 'Agritech', 'Service reliant producteurs locaux et points de vente.', 'La startup aide les agriculteurs a mieux distribuer leurs recoltes.', 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=500', 'Agritech', 'Startup Impact Cameroon 2026'
    UNION ALL SELECT 'SolarBox', 3, 26, 'Energie propre', 'Solution solaire compacte pour petites activites.', 'Une initiative pour rendre l energie plus accessible.', 'https://images.unsplash.com/photo-1508514177221-188b1cf16e9d?w=500', 'Technologie', 'Startup Impact Cameroon 2026'
    UNION ALL SELECT 'Estelle Manka', 1, 20, 'Soul', 'Etudiante et chanteuse a la voix soul.', 'Elle se produit dans les evenements culturels du campus.', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500', 'Voix solo', 'Voix du Campus 2026'
    UNION ALL SELECT 'Les Voix de Ngoa', 2, 23, 'Gospel', 'Ensemble vocal universitaire de douze membres.', 'Le groupe interprete du gospel et des chants traditionnels.', 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=500', 'Chorales', 'Voix du Campus 2026'
    UNION ALL SELECT 'Joel Tchana', 3, 22, 'Afro-fusion', 'Chanteur et guitariste de la scene etudiante.', 'Il associe guitare acoustique et rythmes afro.', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500', 'Voix solo', 'Voix du Campus 2026'
) AS seed
JOIN Campaign AS campaigns ON campaigns.title = seed.campaign_title
JOIN Category AS categories
  ON categories.campaign_id = campaigns.campaign_id AND categories.name = seed.category_name
WHERE NOT EXISTS (
    SELECT 1 FROM Candidate AS existing
    WHERE existing.campaign_id = campaigns.campaign_id
      AND existing.candidate_number = seed.candidate_number
);

INSERT INTO Vote (transaction_ref, candidate_id, campaign_id, payment_method, vote_count, amount_fcfa, created_at)
SELECT seed.transaction_ref, candidates.candidate_id, campaigns.campaign_id,
       seed.payment_method, seed.vote_count, seed.amount_fcfa, seed.created_at
FROM (
    SELECT 'DEMO-202609-001' AS transaction_ref, 'Festival des Talents de Yaounde 2026' AS campaign_title,
           1 AS candidate_number, 'mtn_momo' AS payment_method, 25 AS vote_count, 2250 AS amount_fcfa,
           '2026-09-20 10:15:00' AS created_at
    UNION ALL SELECT 'DEMO-202609-002', 'Festival des Talents de Yaounde 2026', 2, 'orange_money', 50, 4000, '2026-09-21 11:30:00'
    UNION ALL SELECT 'DEMO-202609-003', 'Festival des Talents de Yaounde 2026', 3, 'mtn_momo', 12, 1080, '2026-09-22 14:22:00'
    UNION ALL SELECT 'DEMO-202609-004', 'Startup Impact Cameroon 2026', 1, 'mtn_momo', 30, 5100, '2026-09-23 09:00:00'
    UNION ALL SELECT 'DEMO-202609-005', 'Startup Impact Cameroon 2026', 2, 'orange_money', 15, 2700, '2026-09-24 12:00:00'
    UNION ALL SELECT 'DEMO-202609-006', 'Startup Impact Cameroon 2026', 3, 'mtn_momo', 60, 9600, '2026-09-25 16:30:00'
    UNION ALL SELECT 'DEMO-202609-007', 'Voix du Campus 2026', 1, 'orange_money', 18, 2430, '2026-09-26 10:00:00'
    UNION ALL SELECT 'DEMO-202609-008', 'Voix du Campus 2026', 2, 'mtn_momo', 40, 5100, '2026-09-27 11:15:00'
    UNION ALL SELECT 'DEMO-202609-009', 'Voix du Campus 2026', 3, 'orange_money', 10, 1350, '2026-09-28 19:20:00'
) AS seed
JOIN Campaign AS campaigns ON campaigns.title = seed.campaign_title
JOIN Candidate AS candidates
  ON candidates.campaign_id = campaigns.campaign_id
 AND candidates.candidate_number = seed.candidate_number
WHERE NOT EXISTS (
    SELECT 1 FROM Vote AS existing WHERE existing.transaction_ref = seed.transaction_ref
);

INSERT INTO Payout (campaign_id, amount_fcfa, payment_method, account_holder, wallet_number, status, created_at)
SELECT campaigns.campaign_id, seed.amount_fcfa, seed.payment_method,
       seed.account_holder, seed.wallet_number, seed.status, seed.created_at
FROM (
    SELECT 'Festival des Talents de Yaounde 2026' AS campaign_title, 3000 AS amount_fcfa,
           'mtn_momo' AS payment_method, 'IVote Demo' AS account_holder,
           '670000101' AS wallet_number, 'pending' AS status, '2026-09-24 12:00:00' AS created_at
    UNION ALL SELECT 'Startup Impact Cameroon 2026', 5000, 'orange_money', 'IVote Demo', '690000202', 'completed', '2026-09-26 15:00:00'
    UNION ALL SELECT 'Voix du Campus 2026', 2500, 'mtn_momo', 'IVote Demo', '670000303', 'pending', '2026-09-28 16:30:00'
) AS seed
JOIN Campaign AS campaigns ON campaigns.title = seed.campaign_title
WHERE NOT EXISTS (
    SELECT 1 FROM Payout AS existing
    WHERE existing.campaign_id = campaigns.campaign_id
      AND existing.amount_fcfa = seed.amount_fcfa
      AND existing.wallet_number = seed.wallet_number
      AND existing.created_at = seed.created_at
);

COMMIT;