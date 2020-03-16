# Elgentos_Frontend2FA

This extension is based on [Neyamtux_Authenticator](https://github.com/juashyam/2-Factor-Authentication/), which offers 2FA for the backend.

## Features
- Ability to force frontend 2FA for specific customer groups;
- 2FA is enforced for chosen customer groups meaning the customer has to either setup or authenticate before continuing after logging in;
- All customers in any group can optionally set their own 2FA;
- Open the customer in the backend to be able to reset the 2FA secret;
- Dutch translation files.

## Installation

```
composer require elgentos/frontend2fa
php bin/magento module:enable Elgentos_Frontend2FA
php bin/magento module:enable Neyamtux_Authenticator
php bin/magento setup:upgrade
```

## Integration with other authentication systems

To bypass the 2FA requirement for a specific logged in user, for example if you are using a Single Sign-On system for certain users,
set this value in their customer session:


```
$customerSession->set2faSuccessful(true);
```

## Screenshots

Setup page (in My Account)

![image](https://user-images.githubusercontent.com/431360/53883116-69cdd280-4018-11e9-89a2-c1a471c51d64.png)

2FA authentication after logging in when setup is done

![image](https://user-images.githubusercontent.com/431360/53883181-98e44400-4018-11e9-8bc0-d98676e3527a.png)

Configuration in backend

![image](https://user-images.githubusercontent.com/431360/53885104-3b9ec180-401d-11e9-98bc-ec1a2df3fa6c.png)

2FA reset button in backend

![image](https://user-images.githubusercontent.com/431360/53883268-ce892d00-4018-11e9-84f6-aa1c0fc2e34a.png)

## License

This project is licensed under the MIT License

