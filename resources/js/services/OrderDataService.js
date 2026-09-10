import { ref, set, get, child } from "firebase/database";
import database from "../firebase";
const db = database;

class OrderDataService {
    getOrder(refss) {
        //return db;
        return get(child(ref(db), `orders/${refss}`));
    }
    create(order) {
        return set(ref(db, "orders/" + order.ref), order);

        //return db.push(order);
    }
    update(key, value) {
        return db.child(key).update(value);
    }
    delete(key) {
        return db.child(key).remove();
    }
    deleteAll() {
        return db.remove();
    }
}
export default new OrderDataService();
