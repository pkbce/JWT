const axios = require('axios');

const API_URL = 'http://127.0.0.1:8000/api';
const DB_NAME = 'admin'; // Default user DB

async function testReset() {
    console.log('Testing Check Reset Endpoint...');
    try {
        const response = await axios.post(
            `${API_URL}/consumption/check-reset`,
            { name: DB_NAME }
        );
        console.log('Response:', JSON.stringify(response.data, null, 2));
    } catch (error) {
        console.error('Error:', error.message);
        if (error.response) {
            console.error('Data:', error.response.data);
        }
    }
}

testReset();
