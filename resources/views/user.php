<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Tiket Kereta Api</title>

    <!-- Custom fonts for this template-->
    <link href="http://127.0.0.1:8000/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="http://127.0.0.1:8000/css/sb-admin-2.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php echo $__env->make('layouts.sidebar')->render();?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php echo $__env->make('layouts.navbar')->render();?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Data users</h1>
                    </div>

                    <!-- CONTENT -->
                    <div id="app">
                        <!-- Modal for Creating users -->
                        <div class="modal fade" id="createusersModalCenter" tabindex="-1" role="dialog"
                            aria-labelledby="createusersModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="createusersModalLongTitle">Create users</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="name">username:</label>
                                                    <input v-model="name" type="text" class="form-control"
                                                        id="name"></input>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="">password:</label>
                                                    <input v-model="password" class="form-control"
                                                        id="password"></input>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="">Email</label>
                                                    <input v-model="email" class="form-control" id="email"></input>
                                                </div>
                                            </div>

                                        </form>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary"
                                            v-on:click="createusers">Create</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal for Updating users -->
                        <div class="modal fade" id="updateusersModalCenter" tabindex="-1" role="dialog"
                            aria-labelledby="updateusersModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="updateusersModalLongTitle">Edit users</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="name">Name:</label>
                                                    <input v-model="nameUpdate" type="text" class="form-control"
                                                        name="name" id="nameUpdate"></input>
                                                </div>


                                                <div class="col-md-6 mb-3">
                                                    <label for="">Email</label>
                                                    <input v-model="emailUpdate" class="form-control" name="email"
                                                        id="emailUpdate"></input>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="">password:</label>
                                                    <input v-model="passwordUpdate" class="form-control" name="password"
                                                        id="passwordUpdate"></input>
                                                </div>
                                            </div>

                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary"
                                            v-on:click="updateusers">Edit</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal for Deleting users -->
                        <div class="modal fade" id="deleteusersModalCenter" tabindex="-1" role="dialog"
                            aria-labelledby="deleteusersModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteusersModalLongTitle">Confirm data deletion
                                        </h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure want to delete this users?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger"
                                            v-on:click="deleteusers">Yes</button>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="main" class="container">
                            <div class="col-md-12">
                                <h2>User List</h2>
                                <button type="button" class="mb-4 btn btn-primary" data-toggle="modal"
                                    data-target="#createusersModalCenter">
                                    <span class='fa fa-plus'></span> Create user
                                </button>

                                <div v-if="message" class="alert alert-success" role="alert">
                                    {{ message }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>

                                <table class="table table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <!-- <th scope="col">ID</th> -->
                                            <th scope="col">username</th>
                                            <th scope="col">email</th>
                                            <th scope="col">password</th>
                                            <th style="text-align: center;" colspan="2">action</th>
                                        </tr>
                                    </thead>
                                    <tr v-for="user in users">
                                        <!-- <td>{{ users.id }}</td> -->
                                        <td>{{ user.name }}</td>
                                        <td>{{ user.email }}</td>
                                        <td>Encryted</td>
                                        <td><button class="btn btn-md btn-warning" v-on:click="getEdit(user)"><span
                                                    class='fas fa-edit'></span> </button></td>
                                        <td><button class="btn btn-danger" v-on:click="getDelete(user)"><span
                                                    class='fas fa-trash'></span></button></td>
                                    </tr>
                                </table>

                            </div>
                        </div>
                    </div>
                    <!-- END CONTENT -->
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php echo $__env->make('layouts.footer')->render();?>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>

                    <a class="btn btn-primary" href="{{route('logout')}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                        <span><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</span>
                    </a>

                    <form id="logout-form" href="{{route('logout')}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="http://127.0.0.1:8000/vendor/jquery/jquery.min.js"></script>
    <script src="http://127.0.0.1:8000/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="http://127.0.0.1:8000/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="http://127.0.0.1:8000/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="http://127.0.0.1:8000/vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <!-- <script src="js/demo/chart-area-demo.js"></script>
    <script src="js/demo/chart-pie-demo.js"></script> -->

</body>
<!-- Axios -->
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script>
    var app = new Vue({
        el: '#app',
        data: {
            errors: [],
            message: null,
            users: [],
            usersIdUpdate: null,
            usersIdDelete: null,
            deleteMode: false,
            user: '',
            name: '',
            email: '',
            password: '',
            nameUpdate: '',
            emailUpdate: '',
            passwordUpdate: '',

        },
        mounted: function() {
            this.getusers();
        },
        methods: {
            getusers() {
                axios.get('http://localhost:8000/api/users')
                    .then(response => {
                        this.users = response.data;
                        console.log(response);
                    })
                    .catch(error => {
                        console.log(error);
                    });
            },
            createusers: function() {
                //Hide the create modal
                $('#createusersModalCenter').modal('hide');

                axios.post('http://localhost:8000/api/users', {
                    name: this.name,
                    email: this.email,
                    password: this.password,
                })
                    .then(response => {
                        this.getusers();
                        this.message = "New users has been created";
                        this.resetForm();
                        console.log(response);
                    })
                    .catch(error => {
                        console.log(error);
                    });
            },
            resetForm: function() {
                this.editMode = false;
                this.deleteMode = false;
                this.userIdEdit = null;
                this.name = null;
                this.password = null;
                this.email = null;
            },
            getEdit: function(user) {
                //Show the update modal
                $('#updateusersModalCenter').modal('show');
                this.message = null;
                this.editMode = true;
                this.deleteMode = false;
                this.usersIdEdit = user.id;
                this.nameUpdate = user.name;
                this.passwordUpdate = user.password;
                this.emailUpdate = user.email;
            },
            getDelete: function(users) {
                //Show the delete modal
                $('#deleteusersModalCenter').modal('show')
                this.message = null;
                this.deleteMode = true;
                this.editMode = false;
                this.userIdDelete = users.id;
            },
            updateusers: function() {
                axios.patch(`http://localhost:8000/api/users/${this.userIdEdit}`, {
                    name: this.nameUpdate,
                    email: this.emailUpdate,
                    password: this.passwordUpdate,
                })
                    .then(res => {
                        // handle success
                        this.message = "Your data has been updated";
                        //close the update modal
                        $('#updateusersModalCenter').modal('hide');
                        this.resetForm();
                        this.getusers();
                    })
                    .catch(err => {
                        // handle error
                        console.log(err);
                    })
            },
            // Delete users
            deleteusers: function() {
                axios.delete(`http://localhost:8000/ /users/${this.userIdDelete}`)
                    .then(res => {
                        // handle success
                        this.message = "Your data has been deleted";
                        //close the delete modal
                        $('#deleteusersModalCenter').modal('hide');
                        this.resetForm();
                        this.getusers();
                    })
                    .catch(err => {
                        // handle error
                        console.log(err);
                    })
            }
        }
    })
</script>
</html>

