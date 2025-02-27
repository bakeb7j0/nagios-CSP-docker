# Wizard Usage History
CREATE TABLE IF NOT EXISTS `nagiosxi` . `xi_wizard_history` (
    `row_id` int auto_increment,
    `user_id` int,
    `wizard_name` varchar(255),
    `runs_attempted` int,
    `runs_completed` int,
    `last_run` int,
    primary key(`row_id`),
    unique key(`user_id`, `wizard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
