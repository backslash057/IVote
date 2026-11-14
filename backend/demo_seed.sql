-- ==============================================================================
--  Données de démonstration IVote — USAGE LOCAL UNIQUEMENT (jamais en prod).
--
--  Script autoportant et RELANÇABLE :
--    mysql -h 127.0.0.1 -u <user> -p ivote < demo_seed.sql
--
--  ￭ Il crée lui-même ses organisateurs (aucun compte requis au préalable).
--  ￭ Toutes les instructions sont idempotentes (INSERT IGNORE) : ré-exécuter
--    le script ne duplique rien.
--  ￭ Le super admin `admin@ivote.cm` n'est pas touché par ce script.
-- ==============================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1) ORGANISATEURS (créés uniquement s'ils n'existent pas déjà)
--    Mot de passe temporaire pour tous : TempOrga@123
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO Users (email, name, password) VALUES
('orga1@ivote.cm',      'Paul Ndombe (Events Cam)',        '$2y$12$/4hF1uNr4OZJu57Y0HxMb.AxUzbAHwbTRuG8yoRZkdt7bF8FQI50u'),
('orga2@ivote.cm',      'Aïcha Kone (AfroTech Lab)',       '$2y$12$/4hF1uNr4OZJu57Y0HxMb.AxUzbAHwbTRuG8yoRZkdt7bF8FQI50u'),
('orga3@ivote.cm',      'Samuel Eto (Urban Music Prod)',   '$2y$12$/4hF1uNr4OZJu57Y0HxMb.AxUzbAHwbTRuG8yoRZkdt7bF8FQI50u');

-- ---------------------------------------------------------------------------
-- 2) CAMPAGNES (IDs fixes 1-6)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO Campaign (campaign_id, campaign_code, title, description, image_url, price_per_vote, is_draft, date_cloture, organiser_id)
SELECT seed.campaign_id, seed.campaign_code, seed.title, seed.description, seed.image_url, seed.price_per_vote, seed.is_draft, seed.date_cloture, users.user_id
FROM (
    SELECT 1 AS campaign_id, 'BCDFGHJ' AS campaign_code, 'Élection Miss & Master UY1 2026' AS title,
           'Grande Finale Annuelle de l Excellence et du Leadership Académique.' AS description,
           'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1400' AS image_url,
           100 AS price_per_vote, 0 AS is_draft, '2026-11-30 23:59:59' AS date_cloture,
           'orga1@ivote.cm' AS organizer_email
    UNION ALL SELECT 2, 'KLMNPQR', 'Concours Eloquence Inter-Universités 2026',
           'La compétition nationale de joute oratoire et de débat public.',
           'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=1400',
           150, 0, '2026-10-15 20:00:00', 'orga1@ivote.cm'
    UNION ALL SELECT 3, 'STVWXYZ', 'AfroTech Pitch Awards 2026',
           'Prix du Public pour les Meilleurs Startuppers et Innovateurs Africains.',
           'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400',
           200, 0, '2026-12-01 18:00:00', 'orga2@ivote.cm'
    UNION ALL SELECT 4, 'BCDFGKL', 'Green Innovation Challenge',
           'Concours récompensant les meilleures startups écologiques et durables.',
           'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=1400',
           100, 0, '2026-11-10 23:59:59', 'orga2@ivote.cm'
    UNION ALL SELECT 5, 'MNPQRST', 'Indie Sound Africa Awards',
           'Vote officiel du Meilleur Artiste Révélation Vocale & Musique Urbaine.',
           'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1400',
           100, 0, '2026-10-31 23:59:59', 'orga3@ivote.cm'
    UNION ALL SELECT 6, 'VWXYZBC', 'Gospel Voices Cameroon 2026',
           'Élection de la meilleure chorale et voix sacrée de l année.',
           'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=1400',
           250, 0, '2026-12-25 22:00:00', 'orga3@ivote.cm'
) AS seed
JOIN Users AS users ON users.email = seed.organizer_email;

-- ---------------------------------------------------------------------------
-- 3) CATÉGORIES (IDs fixes 1-13)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO Category (category_id, name, campaign_id) VALUES
(1, 'Miss', 1), (2, 'Master', 1), (3, 'Prix du Public', 1),
(4, 'Plaidoyer Français', 2), (5, 'Plaidoyer Anglais', 2),
(6, 'Tech & Health', 3), (7, 'Agritech', 3),
(8, 'Recyclage', 4), (9, 'Énergie Solaire', 4),
(10, 'Révélation Féminine', 5), (11, 'Révélation Masculine', 5),
(12, 'Voix Solo', 6), (13, 'Chorales', 6);

-- ---------------------------------------------------------------------------
-- 4) CANDIDATS (IDs fixes 1-13)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO Candidate (candidate_id, name, candidate_number, age, theme, description, bio, image_url, category_id, campaign_id) VALUES
(1, 'Amina Diallo', 1, 21, 'Éducation', 'Développeuse et fondatrice de Girls in Code.', 'Passionnée de tech et engagée.', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500', 1, 1),
(2, 'Koffi Mensah', 2, 23, 'Sante', 'Futur chirurgien et capitaine de débat.', 'Pianiste classique et leader étudiant.', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500', 2, 1),
(3, 'Grace Nkem', 3, 22, 'Inclusion', 'Oratrice engagée pour l accès aux soins.', 'Défend l intégrité et la bienveillance.', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500', 3, 1),
(4, 'Marc Atangana', 1, 20, 'Droit', 'Étudiant en M1 Droit Public.', 'Passionné de rhétorique classique.', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500', 4, 2),
(5, 'Jessica Smith', 2, 22, 'Littérature', 'Étudiante en Lettres Modernes.', 'Championne régionale de slam.', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=500', 5, 2),
(6, 'Nadia Eteme', 1, 24, 'Telemedecine', 'Plateforme Santé Connectée.', 'Ingénieure en intelligence artificielle.', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=500', 6, 3),
(7, 'Moussa Traore', 2, 27, 'Irrigation', 'Système d irrigation automatisé solaire.', 'Agro-entrepreneur passionné.', 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=500', 7, 3),
(8, 'CleanPlast Project', 1, 25, 'Recyclage', 'Transformation des déchets plastiques en pave.', 'Équipe de 4 jeunes ingénieurs.', 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=500', 8, 4),
(9, 'SunKits Rural', 2, 28, 'Énergie', 'Kits solaires abordables pour les zones rurales.', 'Startup certifiée impact social.', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=500', 9, 4),
(10, 'Aya Mbaye', 1, 22, 'Afro-Pop', 'Voix soul et afro-pop montante.', 'Auteure-compositrice-interprète.', 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=500', 10, 5),
(11, 'Jean Kondo', 2, 26, 'Afro-Fusion', 'Producteur indépendant et rappeur.', 'Mélange makossa et sonorités trap.', 'https://images.unsplash.com/photo-1501196354995-cbb51c65aaea?w=500', 11, 5),
(12, 'Sœur Marie-Noëlle', 1, 29, 'Gospel Solo', 'Chantre et choriste de renom.', 'Soprano solo en tournée régionale.', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=500', 12, 6),
(13, 'Chœur des Anges de Yaoundé', 2, 30, 'Chorale', 'Chorale polyphonique de 40 membres.', 'Ensemble choral fondé en 2012.', 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=500', 13, 6);

-- ---------------------------------------------------------------------------
-- 5) VOTES (transaction_ref unique = idempotence)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO Vote (transaction_ref, candidate_id, campaign_id, payment_method, vote_count, amount_fcfa, created_at) VALUES
('TXN-20260901-001', 1, 1, 'mtn_momo', 50, 5000, '2026-09-01 10:15:00'),
('TXN-20260901-002', 1, 1, 'orange_money', 120, 12000, '2026-09-01 11:30:00'),
('TXN-20260902-003', 2, 1, 'orange_money', 80, 8000, '2026-09-02 09:00:00'),
('TXN-20260902-004', 3, 1, 'mtn_momo', 10, 1000, '2026-09-02 14:22:00'),
('TXN-20260903-005', 1, 1, 'mtn_momo', 200, 20000, '2026-09-03 18:45:00'),
('TXN-20260905-006', 4, 2, 'orange_money', 20, 3000, '2026-09-05 08:12:00'),
('TXN-20260905-007', 5, 2, 'mtn_momo', 40, 6000, '2026-09-05 12:00:00'),
('TXN-20260906-008', 4, 2, 'mtn_momo', 100, 15000, '2026-09-06 16:30:00'),
('TXN-20260910-009', 6, 3, 'mtn_momo', 150, 30000, '2026-09-10 10:00:00'),
('TXN-20260910-010', 7, 3, 'orange_money', 90, 18000, '2026-09-10 11:15:00'),
('TXN-20260911-011', 6, 3, 'orange_money', 300, 60000, '2026-09-11 19:20:00'),
('TXN-20260912-012', 8, 4, 'mtn_momo', 25, 2500, '2026-09-12 13:00:00'),
('TXN-20260912-013', 9, 4, 'orange_money', 75, 7500, '2026-09-12 15:40:00'),
('TXN-20260915-014', 10, 5, 'orange_money', 500, 50000, '2026-09-15 21:00:00'),
('TXN-20260915-015', 11, 5, 'mtn_momo', 450, 45000, '2026-09-15 21:30:00'),
('TXN-20260916-016', 10, 5, 'mtn_momo', 300, 30000, '2026-09-16 10:05:00'),
('TXN-20260920-017', 12, 6, 'mtn_momo', 40, 10000, '2026-09-20 08:30:00'),
('TXN-20260920-018', 13, 6, 'orange_money', 120, 30000, '2026-09-20 17:50:00'),
('TXN-20260921-019', 13, 6, 'mtn_momo', 200, 50000, '2026-09-21 19:10:00');

-- ---------------------------------------------------------------------------
-- 6) DEMANDES DE PAIEMENT / PAYOUTS (IDs fixes 1-6)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO Payout (payout_id, campaign_id, amount_fcfa, payment_method, account_holder, wallet_number, status, created_at) VALUES
(1, 1, 35000, 'mtn_momo', 'Paul Ndombe', '670000001', 'completed', '2026-09-04 12:00:00'),
(2, 2, 20000, 'orange_money', 'Paul Ndombe', '690000001', 'pending', '2026-09-07 09:30:00'),
(3, 3, 80000, 'mtn_momo', 'Aïcha Kone', '670000002', 'completed', '2026-09-12 14:00:00'),
(4, 4, 5000, 'orange_money', 'Aïcha Kone', '690000002', 'rejected', '2026-09-13 10:00:00'),
(5, 5, 100000, 'orange_money', 'Samuel Eto', '690000003', 'completed', '2026-09-17 16:20:00'),
(6, 6, 80000, 'mtn_momo', 'Samuel Eto', '670000003', 'pending', '2026-09-22 11:45:00');

COMMIT;