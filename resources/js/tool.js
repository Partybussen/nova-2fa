import Authenticate from './pages/Authenticate';
import Recover from './pages/Recover';
import Register from './pages/Register';

Nova.booting((app, store) => {
  Nova.inertia('Nova2fa', Authenticate);
  Nova.inertia('Nova2fa/Recover', Recover);
  Nova.inertia('Nova2fa/Register', Register);
});
