<template>
    <fieldset class="fieldset p-4 bg-base-100 border border-base-300 rounded-box w-64">
        <legend class="fieldset-legend">Which activities are you interested in?</legend>
        <label class="fieldset-label">
            <input type="checkbox" checked="checked" class="checkbox" value="all" />
            I'm Up For Anything! (select all)
        </label>
        <label class="fieldset-label" v-for="(activityType, index) in activityTypes" :key="index">
            <input
                type="checkbox"
                class="checkbox"
                :value="activityType.name"
                v-model="selectedActivityTypes"
            />
            {{ activityType.description }}
        </label>
    </fieldset>
</template>

<script>
import axios from 'axios';

export default {
    components: {
    },
    data() {
        return {
            activityTypes: [],
            selectedActivityTypes: [],
        };
    },
    methods: {
        getActivityTypes() {
            axios
                .get('/api/v1/activity_type')
                .then((response) => {
                    this.activityTypes = response.data;
                })
                .catch((error) => {
                    console.error('Error fetching activity types:', error);
                });
        },
    },
    mounted() {
        this.getActivityTypes();
    },
};
</script>
