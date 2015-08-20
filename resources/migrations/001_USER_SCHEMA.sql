------------------------------------------------------------------------------------------------------------------------
-- Structure for admins users
------------------------------------------------------------------------------------------------------------------------
CREATE TYPE admin_roles AS ENUM ('guest', 'viewer', 'admin');

CREATE TABLE admin (
  id_admin BIGSERIAL PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255),
  role admin_roles NOT NULL
);

INSERT INTO admin (id_admin, username, password, role) VALUES
(1, 'repli2dev', '$2y$10$UUrNvvRbZ0Rm1I3MHqPCc.x03eZWmLHFhtkxvUfr3XhqpwvutffWa', 'admin');