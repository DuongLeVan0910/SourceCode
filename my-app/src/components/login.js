
import React, { useState } from "react";
import { useNavigate } from "react-router-dom"; // Điều hướng sau khi đăng nhập
import "./login.css";

const LoginPage = () => {
    const [isLogin, setIsLogin] = useState(true);
    const [formData, setFormData] = useState({
        email: "",
        password: "",
       
    });
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState("");
    const navigate = useNavigate(); // Hook điều hướng

    const handleChange = (e) => {
        const { name, value } = e.target;
        if (name !== "confirmPassword") {  // Loại bỏ confirmPassword
            setFormData({
                ...formData,
                [name]: value,
            });
        }
        console.log(`📝 Thay đổi: ${name} = ${value}`);

    };
    

    const validateForm = () => {
        const newErrors = {};

        if (!formData.email) {
            newErrors.email = "Email is required";
        } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
            newErrors.email = "Invalid email format";
        }

        if (!formData.password) {
            newErrors.password = "Password is required";
        } else if (formData.password.length < 6) {
            newErrors.password = "Password must be at least 6 characters";
        }

        if (!isLogin) {
            if (!formData.confirmPassword) {
                newErrors.confirmPassword = "Please confirm your password";
            } else if (formData.password !== formData.confirmPassword) {
                newErrors.confirmPassword = "Passwords do not match";
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };
    // const handleSubmit = async (e) => {
    //     e.preventDefault();
    //     console.log("🚀 Hàm handleSubmit đang chạy!");
    //     console.log("📝 Dữ liệu trong formData:", formData); // Kiểm tra dữ liệu nhập vào
    
    //     if (!validateForm()) return;
    
    //     try {
    //         console.log("🟢 Đã vào try block");
    //         // const response = await fetch("http://localhost/Home_React/backend/login.php", { // Đổi localhost thành 127.0.0.1
    //         //     method: "POST",
    //         //     mode: "cors",
    //         //     headers: {
    //         //         "Content-Type": "application/json",
    //         //     },
    //         //     body: JSON.stringify({
    //         //         email: formData.email,
    //         //         password: formData.password,
    //         //     }),
    //         //     // cache: "no-cache", // 🔹 Tránh lỗi cache khi gọi API
    //         //     // credentials: "include",
    //         // });
    //         const response = await fetch("http://127.0.0.1/Home_React/backend/login.php", {
    //             method: "POST",
    //             mode: "cors",
    //             headers: { "Content-Type": "application/json" },
    //             body: JSON.stringify({ email: formData.email, password: formData.password }),
    //         }).catch(err => {
    //             console.error("⛔ Fetch bị lỗi ngay lập tức:", err);
    //             throw err;
    //         });
    //         console.log("📤 Dữ liệu gửi đi:", JSON.stringify({
    //             email: formData.email,
    //             password: formData.password,
    //         })); // Kiểm tra dữ liệu gửi lên server
    //         console.log("🔄 Status:", response.status);
    //         console.log("🔄 Headers:", response.headers);
    //         // const data = await response.json();
    //         // console.log("📥 Phản hồi từ backend:", data);
    //         const text = await response.text(); // Lấy raw response để debug
    //         console.log("📥 Phản hồi từ backend (raw):", text);
    //         const data = JSON.parse(text);
    //         console.log("📥 Phản hồi từ backend (JSON):", data);
    //         if (data.success) {
                
    //             alert("Đăng nhập thành công!");
    //             navigate("/dashboard");
    //         } else {
    //             alert("Lỗi: " + data.message);
    //         }
    //     } catch (error) {
    //         console.error("⛔ Lỗi khi gọi API:", error);
    //         alert("Không thể kết nối đến server!");
    //     }
    // };
    const handleSubmit = async (e) => {
        e.preventDefault();
        console.log("🚀 Hàm handleSubmit đang chạy!");
        console.log("📝 Dữ liệu trong formData:", formData); // Kiểm tra dữ liệu nhập vào
    
        if (!validateForm()) return;
    
        try {
            console.log("🟢 Đã vào try block");
            
            // Kiểm tra JSON trước khi gửi đi
            const bodyData = JSON.stringify({
                email: formData.email,
                password: formData.password,
            });
            console.log("📤 Dữ liệu gửi đi:", bodyData);
    
            const response = await fetch("http://127.0.0.1/Home_React/backend/login.php", {
                method: "POST",
                mode: "cors",
                headers: { "Content-Type": "application/json" },
                body: bodyData,
            });
    
            console.log("🔄 Status:", response.status);
    
            // Kiểm tra response headers
            response.headers.forEach((value, name) => {
                console.log(`🔄 Header: ${name} = ${value}`);
            });
    
            const text = await response.text(); // Lấy raw response để debug
            console.log("📥 Phản hồi từ backend (raw):", text);
    
            let data;
            try {
                data = JSON.parse(text);
                console.log("📥 Phản hồi từ backend (JSON):", data);
            } catch (error) {
                console.error("❌ Lỗi khi parse JSON từ response:", error);
                alert("Lỗi server, phản hồi không đúng định dạng JSON!");
                return;
            }
    
            if (data.success) {
                alert("✅ Đăng nhập thành công!");
                navigate("/dashboard");
            } else {
                alert("❌ Lỗi: " + data.message);
            }
        } catch (error) {
            console.error("⛔ Lỗi khi gọi API:", error);
            alert("Không thể kết nối đến server!");
        }
    };
    
    
    const toggleForm = () => {
        setIsLogin(!isLogin);
        setErrors({});
        setMessage("");
        setFormData({
            email: "",
            password: "",
            confirmPassword: "",
        });
    };

    return (
        <div className="login-container">
            <div className="shape-top-left"></div>
            <div className="shape-bottom-right"></div>

            <div className="form-container">
                <h1 className="form-title">{isLogin ? "Sign In" : "Sign Up"}</h1>
                {message && <p className="message">{message}</p>}

                <form onSubmit={handleSubmit}>
                    <div className="input-group">
                        <input
                            type="email"
                            name="email"
                            placeholder="Email Address"
                            value={formData.email}
                            onChange={handleChange}
                            className="input-field"
                        />
                        {errors.email && <p className="error-text">{errors.email}</p>}
                    </div>

                    <div className="input-group">
                        <input
                            type="password"
                            name="password"
                            placeholder="Password"
                            value={formData.password}
                            onChange={handleChange}
                            className="input-field"
                        />
                        {errors.password && <p className="error-text">{errors.password}</p>}
                    </div>

                    {!isLogin && (
                        <div className="input-group">
                            <input
                                type="password"
                                name="confirmPassword"
                                placeholder="Re-enter password"
                                value={formData.confirmPassword}
                                onChange={handleChange}
                                className="input-field"
                            />
                            {errors.confirmPassword && (
                                <p className="error-text">{errors.confirmPassword}</p>
                            )}
                        </div>
                    )}

                    <button type="submit" className="submit-button">
                        {isLogin ? "Sign In" : "Create Account"}
                    </button>
                </form>

                <div className="toggle-text">
                    <p>
                        {isLogin ? "Don't have an account? " : "Already Have An Account? "}
                        <button onClick={toggleForm} className="toggle-link">
                            {isLogin ? "Sign Up" : "Sign In"}
                        </button>
                    </p>
                </div>
            </div>
        </div>
    );
};

export default LoginPage;
