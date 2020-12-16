<?php
declare(strict_types=1);

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Token;

/**
 * Class ApiTest
 */
class TokenTest extends TestCase {
    use DatabaseTransactions;

    /**
     * Ελέγχουμε το ότι κατά την είσοδο στην εφαρμογή δημιουργούνται
     * σωστά τα token καθώς και το ότι σε κάθε περίπτωση επιστρέφονται
     * τα κατάλληλα responses.
     *
     * @return void
     */
    public function testCreation () : void {
        $test_user = User::factory()->create();
        $this->post('/api/login', ['email' => $test_user->email, 'password' => 'password'])
             ->seeJson([
                'token_type' => 'Bearer'
             ]);

        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $bearer_token = $response->json()['access_token'];
        $refresh_token = $response->json()['refresh_token'];
        $this->seeInDatabase('tokens', ['access_token'=> $bearer_token, 'refresh_token' => $refresh_token,
            'is_expired' => 0, 'user_id' => $test_user->id]);

        $this->post('/api/login', ['email' => $test_user->email, 'password' => 'password!'])
             ->seeJsonEquals([
                'message' => 'Unauthorized',
                'reason' => 'Incorrect username or password.'
             ]);
    }

    // /**
    //  * Ελέγχουμε ότι τα token μπορούν να χρησιμοποιηθούν καθώς και
    //  * το ότι όταν λήγουν επιστρέφουν το κατάλληλο response.
    //  *
    //  * Για το test χρησιμοποιούνται οι σταθερές Token::REFRESH_TOKEN_LIFESPAN__TESTING και
    //  * Token::ACCESS_TOKEN_LIFESPAN__TESTING αντί γιa Token::REFRESH_TOKEN_LIFESPAN και
    //  * Token::ACCESS_TOKEN_LIFESPAN
    //  *
    //  * @return void
    //  */
    public function testValidation () : void {
        $test_user = User::factory()->create();
        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $bearer_token = $response->json()['access_token'];

        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearer_token,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'first_name' => $test_user->first_name,
            'last_name' => $test_user->last_name
        ]);

        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $incorrect_token = $response->json()['access_token'].'!';

        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$incorrect_token,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'error_id' => 3,
            'error' => 'invalid_grant',
            'error_description' => 'This access token does not exist'
        ]);

        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $bearer_token = $response->json()['access_token'];
        sleep(Token::ACCESS_TOKEN_LIFESPAN__TESTING);
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearer_token,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'error_id' => 1,
            'error' => 'invalid_token',
            'error_description' => 'The access token is expired'
        ]);

        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $bearer_token = $response->json()['access_token'];
        sleep(Token::REFRESH_TOKEN_LIFESPAN__TESTING);
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearer_token,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'error_id' => 2,
            'error' => 'invalid_token',
            'error_description' => 'The refresh token is expired'
        ]);

    }

    // /**
    //  * Ελέγχουμε ότι όταν λήγει το access token, με την χρήση του
    //  * κατάλληλου refresh token δημιουργείται και επιστρέφεται response
    //  * που περιέχει καινούργιο ζεύγος refresh-access token.
    //  *
    //  * Παράλληλα ελέγχουμε ότι όταν λήγει το refresh token δεν
    //  * μπορεί να δημιουργηθεί καινούργιο ζεύγος refresh-access token
    //  *
    //  * Για το test χρησιμοποιούνται οι σταθερές Token::REFRESH_TOKEN_LIFESPAN__TESTING και
    //  * Token::ACCESS_TOKEN_LIFESPAN__TESTING αντί γιa Token::REFRESH_TOKEN_LIFESPAN και
    //  * Token::ACCESS_TOKEN_LIFESPAN
    //  *
    //  * @return void
    //  */
    public function testTokenRefresh () : void {
        $test_user = User::factory()->create();
        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $refresh_token = $response->json()['refresh_token'];
        sleep(Token::ACCESS_TOKEN_LIFESPAN__TESTING);
        $response = $this->call('GET','/api/refresh_token', ['refresh_token' => $refresh_token]);
        $bearer_token = $response->json()['access_token'];
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearer_token,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'first_name' => $test_user->first_name,
            'last_name' => $test_user->last_name
        ]);

        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $refresh_token = $response->json()['refresh_token'];
        $response = $this->call('GET','/api/refresh_token', ['refresh_token' => $refresh_token]);
        $bearer_token = $response->json()['access_token'];
        sleep(Token::REFRESH_TOKEN_LIFESPAN__TESTING);
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearer_token,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'error_id' => 2,
            'error' => 'invalid_token',
            'error_description' => 'The refresh token is expired'
        ]);

        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $refresh_token = $response->json()['refresh_token'];
        $this->get('/api/refresh_token', ['refresh_token' => $refresh_token.'!'])
             ->seeJsonEquals([
                 'error_id' => 3,
                 'error' => 'invalid_grant',
                 'error_description' => 'This access token does not exist'
             ]);
    }

    /**
     * Ελέγχουμε ότι τα token λήγουν κατά το logout
     *
     * @return void
     */
    public function testExpiration () : void {
        $test_user = User::factory()->create();
        $response = $this->call('POST','/api/login', ['email' => $test_user->email, 'password' => 'password']);
        $bearer_token = $response->json()['access_token'];
        $this->seeInDatabase('tokens', ['access_token' => $bearer_token,
            'is_expired' => 0, 'user_id' => $test_user->id]);
        $this->post('/api/logout',[],
        [
            'Authorization' => 'Bearer '.$bearer_token,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'message' => 'Token revoked'
        ]);
    }

}
