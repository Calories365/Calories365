import axios from './axios';

/**
 * Get the current Russian language status
 * @returns {Promise}
 */
export function getLanguageStatus() {
    return axios.get('/api/language/status')
        .catch(error => {
            console.error('Error fetching language status:', error);
            // Return a default response that will enable Russian
            return {
                data: {
                    success: true,
                    data: {
                        russian_language_enabled: true
                    }
                }
            };
        });
}

export default {
    getLanguageStatus
}; 