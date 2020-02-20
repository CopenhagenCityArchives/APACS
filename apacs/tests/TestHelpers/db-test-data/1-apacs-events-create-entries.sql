INSERT INTO `apacs`.`apacs_users`
(`id`, `username`)
VALUES
(797, 'User_1'),
(798, 'User_2'),
(799, 'User_3'),
(800, 'User_4');


INSERT INTO `apacs`.`apacs_events`
(`id`, `users_id`, `collections_id`, `tasks_id`, `units_id`, `pages_id`, `posts_id`, `event_type`, `timestamp`, `backup`)
VALUES
('1', '797', '1', '1', '96', '127952', '307527', 'create', now(), NULL),
('2', '797', '1', '1', '96', '127952', '307527', 'create', now(), NULL),
('3', '798', '2', '2', '97', '127953', '307528', 'create', now(), NULL),
('4', '799', '3', '3', '98', '127954', '307529', 'create', TIMESTAMPADD(DAY,-30,now()), NULL),
('5', '800', '4', '3', '98', '127954', '307529', 'create', TIMESTAMPADD(DAY,-30,now()), NULL),
('6', '800', '4', '3', '98', '127954', '307529', 'create', TIMESTAMPADD(DAY,-30,now()), NULL);