<?php

namespace App;

class AppConstants
{
    const siteName = config('app.siteName');
    const noreplyEmail = config('app.noreplyEmail');
    const siteUrl = config('app.siteUrl');
    const monnifyBaseUrl = config("app.monnifyBaseUrl");
    const monnifyAPIKey = config('app.monnifyAPIKey');
    const monnifySecretKey = config("app.monnifySecretKey");
    const monnifyContractCode = config("app.monnifyContractCode");
    const smePlugPrivateKey = config("app.smePlugPrivateKey");
    const vtPassUsername = config("app.vtPassUsername");
    const vtPassPassword = config("app.vtPassPassword");
    const vtPassBaseUrl = config("app.vtPassBaseUrl");
    const smartSMSBaseUrl = config("app.smartSMSBaseUrl");
    const smartSMStoken = config("app.smartSMStoken");
    const simServerBaseUrl = config("app.simServerBaseUrl");
    const simServerApiKey = config("app.simServerApiKey");
    const egmsApiKey = config("app.egmsApiKey");
    const egmsBaseUrl = config("app.egmsBaseUrl");
}
