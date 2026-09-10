<template>

</template>

<script>
const token = window.localStorage.getItem("token");
export default {
    name:"VerifyInfo",
    data (){
        return  {
            bvn:null,
            nin:null,
        }
    },
    mounted(){
        this.getInfo();
        this.verifyInfo()
    },
    methods: {
    verifyInfo(){
        if(this.bvn == null && this.nin == null){
            this.$router.push("/dashboard/identity-verification")
        }
    },
    getInfo(){
        var url = `/api/load-home`;
      this.isloading = true;

      axios
        .get(url, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
        })
        .then(({ data }) => {
          this.bvn = data.data.bvn;
          this.nin = data.data.nin;
        console.log("BVN" +this.bvn);
        console.log("NIN" +this.nin);
          this.isloading = false;
        })
        .catch(({ response }) => {
          this.$toasted.show(error.response.data.data);
          this.isloading = false;
        });
    }
    }
}
</script>

<style>

</style>
