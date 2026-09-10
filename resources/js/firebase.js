// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";

import * as firebase from "firebase/database";
//import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
    apiKey: import.meta.env.MIX_FIREBASE_API_KEY,
    authDomain: "winich-aee50.firebaseapp.com",
    databaseURL: "https://winich-aee50-default-rtdb.firebaseio.com",
    projectId: "winich-aee50",
    storageBucket: "winich-aee50.appspot.com",
    messagingSenderId: "1090834089924",
    appId: import.meta.env.MIX_FIREBASE_APP_ID,
    measurementId: "G-2G6BNTT8VQ",
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
//const analytics = getAnalytics(app);
const database = firebase.getDatabase(app);
export default database;
