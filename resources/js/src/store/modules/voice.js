import { uploadVoiceRecord } from '@/api/voice.js';

export const mutationTypes = {
    RECORDING_START: 'voice/RECORDING_START',
    RECORDING_STOP: 'voice/RECORDING_STOP',
    RECORDING_PROCESS: 'voice/RECORDING_PROCESS',
    RECORDING_COMPLETE: 'voice/RECORDING_COMPLETE',
    RECORDING_ERROR: 'voice/RECORDING_ERROR',
    RECORDING_RESET: 'voice/RECORDING_RESET',
    UPLOAD_START: 'voice/UPLOAD_START',
    UPLOAD_SUCCESS: 'voice/UPLOAD_SUCCESS',
    UPLOAD_FAILURE: 'voice/UPLOAD_FAILURE'
};

export const actionTypes = {
    startRecording: 'voice/startRecording',
    stopRecording: 'voice/stopRecording',
    uploadRecording: 'voice/uploadRecording',
    resetRecording: 'voice/resetRecording'
};

const state = {
    isRecording: false,
    isProcessing: false,
    audioBlob: null,
    isUploading: false,
    uploadSuccess: false,
    uploadError: null
};

const mutations = {
    [mutationTypes.RECORDING_START](state) {
        state.isRecording = true;
        state.isProcessing = false;
        state.audioBlob = null;
        state.uploadSuccess = false;
        state.uploadError = null;
    },
    [mutationTypes.RECORDING_STOP](state) {
        state.isRecording = false;
        state.isProcessing = true;
    },
    [mutationTypes.RECORDING_PROCESS](state) {
        state.isProcessing = true;
    },
    [mutationTypes.RECORDING_COMPLETE](state, audioBlob) {
        state.isProcessing = false;
        state.audioBlob = audioBlob;
    },
    [mutationTypes.RECORDING_ERROR](state, error) {
        state.isRecording = false;
        state.isProcessing = false;
        state.uploadError = error;
    },
    [mutationTypes.RECORDING_RESET](state) {
        state.isRecording = false;
        state.isProcessing = false;
        state.audioBlob = null;
        state.isUploading = false;
        state.uploadSuccess = false;
        state.uploadError = null;
    },
    [mutationTypes.UPLOAD_START](state) {
        state.isUploading = true;
    },
    [mutationTypes.UPLOAD_SUCCESS](state) {
        state.isUploading = false;
        state.uploadSuccess = true;
    },
    [mutationTypes.UPLOAD_FAILURE](state, error) {
        state.isUploading = false;
        state.uploadError = error;
    }
};

const actions = {
    [actionTypes.startRecording]({ commit }) {
        commit(mutationTypes.RECORDING_START);
    },
    [actionTypes.stopRecording]({ commit }) {
        commit(mutationTypes.RECORDING_STOP);
    },
    async [actionTypes.uploadRecording]({ commit, state }) {
        if (!state.audioBlob) {
            commit(mutationTypes.UPLOAD_FAILURE, 'Помилка');
            return;
        }

        commit(mutationTypes.UPLOAD_START);

        try {
            const response = await uploadVoiceRecord(state.audioBlob);
            commit(mutationTypes.UPLOAD_SUCCESS);

            return response;
        } catch (error) {
            commit(mutationTypes.UPLOAD_FAILURE, error.response?.data?.message || 'Помилка');
            throw error;
        }
    },
    [actionTypes.resetRecording]({ commit }) {
        commit(mutationTypes.RECORDING_RESET);
    }
};

const getters = {
    isRecording: state => state.isRecording,
    isProcessing: state => state.isProcessing,
    audioBlob: state => state.audioBlob,
    isUploading: state => state.isUploading,
    uploadSuccess: state => state.uploadSuccess,
    uploadError: state => state.uploadError,
    hasRecording: state => !!state.audioBlob
};

export default {
    state,
    mutations,
    actions,
    getters
};
