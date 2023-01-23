<template>
  <div>
    <Head title="Two factor authentication (2FA)" />

    <form
      @submit.prevent="verify"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 max-w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6 text-90">
        {{ __('2-step verification recovery') }}
      </h2>

      <p class="text-center font-normal mb-6 text-90">
        {{ __('Enter one of your recovery codes. If you don\'t have any, please contact the system administrator.')}}
      </p>

      <DividerLine />

      <div class="mb-6">
        <label class="block mb-2" for="recovery_code">{{ __('Recovery code') }}</label>
        <input
          v-model="form.recovery_code"
          class="form-control form-input form-input-bordered w-full"
          :class="{ 'form-input-border-error': form.errors.has('recovery_code') }"
          id="recovery_code"
          type="text"
          name="recovery_code"
          autofocus=""
          required
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('recovery_code')">
          {{ form.errors.first('recovery_code') }}
        </HelpText>
      </div>

      <DefaultButton class="w-full flex justify-center mb-6" type="submit">
        <span>
          {{ __('Verify') }}
        </span>
      </DefaultButton>

      <div
        class="ml-auto"
      >
        <Link
          :href="$url(authenticateUrl)"
          class="text-gray-500 font-bold no-underline"
          v-text="__('Try again with a code?')"
        />
      </div>

    </form>
  </div>
</template>

<script>
import Auth from '../../../vendor/laravel/nova/resources/js/layouts/Auth'

export default {

  layout: Auth,

  data: () => ({
    form: Nova.form({
      recovery_code: ''
    })
  }),

  computed: {
    authenticateUrl() {
      return '/nova-2fa';
    }
  },

  mounted() {
    //
  },

  methods: {
    async verify() {
      try {
        const { redirect } = await this.form.post('/nova-vendor/nova-2fa/recover')

        if (redirect !== undefined && redirect !== null) {
          let path = { url: redirect, remote: true }
          Nova.visit(path)
        }
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }
    },
  }
}
</script>

<style>

</style>
