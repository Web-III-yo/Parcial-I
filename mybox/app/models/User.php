<?php
class User
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::connection();
    }

    public function authenticate(string $username, string $password): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT nombre, usuario FROM usuarios WHERE usuario = :usuario AND contra = MD5(:contra) LIMIT 1'
        );
        $statement->bindValue(':usuario', $username, PDO::PARAM_STR);
        $statement->bindValue(':contra', $password, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $username, string $password, string $name, string $email): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO usuarios (usuario, contra, nombre, email) VALUES (:usuario, MD5(:contra), :nombre, :email)'
        );
        $statement->bindValue(':usuario', $username, PDO::PARAM_STR);
        $statement->bindValue(':contra', $password, PDO::PARAM_STR);
        $statement->bindValue(':nombre', $name, PDO::PARAM_STR);
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $statement->execute();
    }
}
