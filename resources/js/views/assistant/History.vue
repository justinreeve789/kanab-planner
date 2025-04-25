<template>
    <div class="topics max-h-[350px] w-full" style="overflow-y: scroll;">
        <div class="collapse collapse-arrow">
            <input type="checkbox" name="topics-history" checked="checked" />
            <div class="collapse-title flex justify-between items-center px-4 py-2 text-lg bg-gradient-to-r from-blue-50 to-cyan-100">
                <span @click="">General</span>
                <img src="images/icons/light-bulb.svg" alt="Icon" class="h-6 mr-6 w-6" />
            </div>
            <div class="collapse-content text-sm px-4 py-2">
            </div>
        </div>
        <div class="collapse collapse-arrow">
            <input type="checkbox" name="topics-history" checked="checked" />
            <div class="collapse-title flex justify-between items-center px-4 py-2 text-lg bg-gradient-to-r from-green-100 to-green-200">
                <span>Restaurants</span>
                <img src="images/icons/dining.svg" alt="Icon" class="h-6 mr-6 w-6" />
            </div>
            <div class="collapse-content text-sm px-4 py-2">
            </div>
        </div>
        <div class="collapse collapse-arrow">
            <input type="checkbox" name="topics-history" checked="checked" />
            <div class="collapse-title flex justify-between items-center px-4 py-2 text-lg bg-gradient-to-r from-orange-100 to-red-100">
                <span>Trails</span>
                <img src="images/icons/hiking.svg" alt="Icon" class="h-6 mr-6 w-6" />
            </div>
            <div class="collapse-content text-sm px-4 py-2">
            </div>
        </div>
    </div>
</template>

<script>
export default {
    mounted() {}
}
</script>
