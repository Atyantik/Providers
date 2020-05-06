<?php

namespace SocialiteProviders\WeChatServiceAccount;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WECHAT_SERVICE_ACCOUNT';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['snsapi_userinfo'];

    private $openId;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://open.weixin.qq.com/connect/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $openId = isset($this->credentialsResponseBody['openid']) ?
            $this->credentialsResponseBody['openid'] : $this->openId;

        // HACK: Tencent return id when grant token, and can not get user by this token
        if (in_array('snsapi_base', $this->getScopes())) {
            return ['openid' => $openId];
        }
        $response = $this->getHttpClient()->get('https://api.weixin.qq.com/sns/userinfo', [
            'query' => [
                'access_token' => $token, // HACK: Tencent use token in Query String, not in Header Authorization
                'openid'       => $openId, // HACK: Tencent need id, but other platforms don't need
                'lang'         => 'zh_CN',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            // HACK: use unionid as user id
            'id'       => in_array('unionid', $this->getScopes()) ? $user['unionid'] : $user['openid'],
            // HACK: Tencent scope snsapi_base only return openid
            'nickname' => isset($user['nickname']) ? $user['nickname'] : null,
            'name'     => null,
            'email'    => null,
            'avatar'   => isset($user['headimgurl']) ? $user['headimgurl'] : null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'appid'      => $this->clientId,
            'secret'     => $this->clientSecret,
            'code'       => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);
        unset($fields['client_id']);
        $fields['appid'] = $this->clientId; // HACK: Tencent use appid, not app_id or client_id

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatScopes(array $scopes, $scopeSeparator)
    {
        // HACK: unionid is a faker scope for user id
        if (in_array('unionid', $scopes)) {
            unset($scopes[array_search('unionid', $scopes)]);
        }
        // HACK: use scopes() instead of setScopes()
        // docs: https://laravel.com/docs/socialite#access-scopes
        if (in_array('snsapi_base', $scopes)) {
            unset($scopes[array_search('snsapi_userinfo', $scopes)]);
        }

        return implode($scopeSeparator, $scopes);
    }

    public function setOpenId($openId)
    {
        $this->openId = $openId;

        return $this;
    }
}