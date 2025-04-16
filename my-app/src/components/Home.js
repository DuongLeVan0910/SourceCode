import React, { useState } from 'react';
import './Home.css'; 

function Home() {
  const [students, setStudents] = useState([
    {
      stt: 1,
      mssv: 'DH521019999',
      hoTen: 'Nguyễn Văn A',
      khoa: 'CNTT',
      lop: 'D21_TH11',
      ngaySinh: '1/1/2003',
    },
    // Thêm dữ liệu sinh viên khác nếu cần
  ]);

  const [search, setSearch] = useState({
    mssv: '',
    lop: '',
    ngaySinh: '',
    hoTen: '',
    khoa: '',
  });

  const handleInputChange = (e) => {
    setSearch({ ...search, [e.target.name]: e.target.value });
  };

  const handleSearch = () => {
    // Logic tìm kiếm
    const filteredStudents = students.filter(student =>
      student.mssv.toLowerCase().includes(search.mssv.toLowerCase()) &&
      student.lop.toLowerCase().includes(search.lop.toLowerCase()) &&
      student.ngaySinh.toLowerCase().includes(search.ngaySinh.toLowerCase()) &&
      student.hoTen.toLowerCase().includes(search.hoTen.toLowerCase()) &&
      student.khoa.toLowerCase().includes(search.khoa.toLowerCase())
    );
    setStudents(filteredStudents);
  };
    
  const handleAddStudent = () => {
      console.log("Chức năng thêm sinh viên");
  };

  const handleDeleteStudent = (mssv) => {
      const updatedStudents = students.filter(student => student.mssv !== mssv);
        setStudents(updatedStudents);
        console.log("Xóa sinh viên có MSSV:", mssv);
  };

  const handleEditStudent = (mssv) => {
       console.log("Sửa sinh viên có MSSV:", mssv);
  }


  return (
      <div className="main-content">
        <div className="header">
            <div className="admin-icon">👤</div>
          <div className="admin-text">ADMIN</div>
        </div>
        <h2>Danh sách sinh viên</h2>

        <div className="search-area">
          <input
            type="text"
            name="mssv"
            placeholder="MSSV"
            value={search.mssv}
            onChange={handleInputChange}
          />
          <input
            type="text"
            name="lop"
            placeholder="Lớp"
            value={search.lop}
            onChange={handleInputChange}
          />
          <input
            type="text"
            name="ngaySinh"
            placeholder="Ngày Sinh"
            value={search.ngaySinh}
            onChange={handleInputChange}
          />
          <input
            type="text"
            name="hoTen"
            placeholder="Họ Tên"
            value={search.hoTen}
            onChange={handleInputChange}
          />
            <input
            type="text"
            name="khoa"
            placeholder="Khoa"
            value={search.khoa}
            onChange={handleInputChange}
          />
          <button className="search-button" onClick={handleSearch}>
            Tìm kiếm
          </button>
            <button className="add-button" onClick={handleAddStudent}>
                Thêm sinh viên
            </button>
        </div>
        

        <table className="student-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>MSSV</th>
              <th>Họ Tên</th>
              <th>Khoa</th>
              <th>Lớp</th>
              <th>Ngày Sinh</th>
              <th>Quản Lý</th>
            </tr>
          </thead>
          <tbody>
            {students.map((student, index) => (
              <tr key={index}>
                <td>{student.stt}</td>
                <td>{student.mssv}</td>
                <td>{student.hoTen}</td>
                <td>{student.khoa}</td>
                <td>{student.lop}</td>
                <td>{student.ngaySinh}</td>
                <td className="action-buttons">
                                    <button className="delete-button" onClick={() => handleDeleteStudent(student.mssv)}>Xóa</button>
                                    <button className = "edit-button" onClick={()=> handleEditStudent(student.mssv)}>Sửa</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
  );
}

export default Home;