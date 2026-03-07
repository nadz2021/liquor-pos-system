<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class User {

    public static function all(){
        return DB::pdo()->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    }

    public static function create($d){
        $pdo = DB::pdo();
        $st = $pdo->prepare("INSERT INTO users (name,username,pin,role,is_active) VALUES (?,?,?,?,1)");
        $st->execute([$d['name'],$d['username'],$d['pin'],$d['role']]);
    }

    public static function toggle($id){
        $pdo = DB::pdo();
        $pdo->prepare("UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id=?")->execute([$id]);
    }

    public static function findByUsername(string $username): ?array
{
    $pdo = \App\Core\DB::pdo();

    $st = $pdo->prepare("
        SELECT *
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $st->execute([$username]);

    $user = $st->fetch();

    return $user ?: null;
}
}
