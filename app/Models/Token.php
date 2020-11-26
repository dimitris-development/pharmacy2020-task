<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Token extends Model {
    const CREATED_AT = 'bearer_token_creation_date';
    const UPDATED_AT = 'access_refresh_pair_creation_date';
    const ACCESS_TOKEN_LIFESPAN = 3600;
    const REFRESH_TOKEN_LIFESPAN = 1296000; //15 μέρες

    const ACCESS_TOKEN_LIFESPAN__TESTING = 10;
    const REFRESH_TOKEN_LIFESPAN__TESTING = 20;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tokens';
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    
    protected $fillable = [
        'access_token', 'refresh_token', 'user_id', 'is_expired'
    ];

}