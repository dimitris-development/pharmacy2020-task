<?php /** @noinspection ALL */

/** @noinspection ALL */

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Token;

/**
 * Class ApiTest
 */
class ApiTest extends TestCase {
    use DatabaseTransactions;

    /**
     * Ελέγχουμε το ότι κατά την είσοδο στην εφαρμογή δημιουργούνται
     * σωστά τα token καθώς και το ότι σε κάθε περίπτωση επιστρέφονται
     * τα κατάλληλα responses.
     *
     * @return void
     */
    public function testTokenCreation () {
        $testUser = User::factory()->create();
        $this->post('/api/login', ["email" => $testUser->email, "password" => "password"])
             ->seeJson([
                'token_type' => 'Bearer'
             ]);

        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $bearerToken = $response->json()['access_token'];
        $refreshToken = $response->json()['refresh_token'];
        $this->seeInDatabase('tokens', ["access_token" => $bearerToken, "refresh_token" => $refreshToken, "is_expired" => 0, "user_id" => $testUser->id]);

        $this->post('/api/login', ["email" => $testUser->email, "password" => "password!"])
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
    //  * Token::ΑCCESS_TOKEN_LIFESPAN__TESTING αντί γιa Token::REFRESH_TOKEN_LIFESPAN και
    //  * Token::ΑCCESS_TOKEN_LIFESPAN
    //  *
    //  * @return void
    //  */
    public function testTokenValidation () {
        $testUser = User::factory()->create();
        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $bearerToken = $response->json()['access_token'];

        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearerToken,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'first_name' => $testUser->first_name,
            'last_name' => $testUser->last_name
        ]);

        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $incorrectBearerToken = $response->json()['access_token']."!";

        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$incorrectBearerToken,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            "error" => "invalid_grant"
        ]);

        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $bearerToken = $response->json()['access_token'];
        sleep(Token::ACCESS_TOKEN_LIFESPAN__TESTING);
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearerToken,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            "error" => "invalid_token",
            "error_description"=> "The access token is expired"
        ]);

        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $bearerToken = $response->json()['access_token'];
        sleep(Token::REFRESH_TOKEN_LIFESPAN__TESTING);
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearerToken,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            "error" => "invalid_token",
            "error_description" => "The refresh token is expired"
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
    //  * Token::ΑCCESS_TOKEN_LIFESPAN__TESTING αντί γιa Token::REFRESH_TOKEN_LIFESPAN και
    //  * Token::ΑCCESS_TOKEN_LIFESPAN
    //  *
    //  * @return void
    //  */
    public function testTokenRefresh () {
        $testUser = User::factory()->create();
        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $refreshToken = $response->json()['refresh_token'];
        sleep(Token::ACCESS_TOKEN_LIFESPAN__TESTING);
        $response = $this->call('POST','/api/refresh_token', ["refresh_token" => $refreshToken]);
        $bearerToken = $response->json()['access_token'];
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearerToken,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            'first_name' => $testUser->first_name,
            'last_name' => $testUser->last_name
        ]);

        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $refreshToken = $response->json()['refresh_token'];
        $response = $this->call('POST','/api/refresh_token', ["refresh_token" => $refreshToken]);
        $bearerToken = $response->json()['access_token'];
        sleep(Token::REFRESH_TOKEN_LIFESPAN__TESTING);
        $this->get('/api/get_user_info',
        [
            'Authorization' => 'Bearer '.$bearerToken,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            "error" => "invalid_token",
            "error_description" => "The refresh token is expired"
        ]);

        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $refreshToken = $response->json()['refresh_token'];
        $this->post('/api/refresh_token', ["refresh_token" => $refreshToken."!"])
             ->seeJsonEquals([
                "error" => "invalid_grant"
             ]);
    }

    /**
     * Ελέγχουμε ότι τα token λήγουν κατά το logout
     *
     * @return void
     */
    public function testTokenExpiration () {
        $testUser = User::factory()->create();
        $response = $this->call('POST','/api/login', ["email" => $testUser->email, "password" => "password"]);
        $bearerToken = $response->json()['access_token'];
        $this->seeInDatabase('tokens', ["access_token" => $bearerToken, "is_expired" => 0, "user_id" => $testUser->id]);
        $this->post('/api/logout',[],
        [
            'Authorization' => 'Bearer '.$bearerToken,
            'Accept' => 'application/json'
        ])->seeJsonEquals([
            "message" => "Token revoked"
        ]);
    }

}
